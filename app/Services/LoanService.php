<?php

namespace App\Services;


use App\Events\LoanApplicationStatusChanged;
use App\Events\LoanApprovedByBranchManager;
use App\Events\LoanApprovedByGlobalManager;
use App\Events\LoanDisApprovedByBranchManager;
use App\Events\LoanDisbursed;
use App\Events\NewLoanCreated;
use App\GraphQL\Errors\GraphqlError;
use App\Models\Enums\DisbursementStatus;
use App\Models\Enums\LoanApplicationStatus;
use App\Models\Enums\LoanConditionStatus;
use App\Models\Enums\LoanDefaultStatus;
use App\Models\enums\TransactionType;
use App\Models\Loan;
use App\Repositories\Interfaces\LoanRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class LoanService
{
    /**
     * @var LoanRepositoryInterface
     */
    private $loanRepository;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * LoanService constructor.
     *
     * @param LoanRepositoryInterface $loanRepository
     * @param TransactionService $transactionService
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(LoanRepositoryInterface $loanRepository, TransactionService $transactionService, UserRepositoryInterface $userRepository)
    {
        $this->loanRepository = $loanRepository;
        $this->transactionService = $transactionService;
        $this->userRepository = $userRepository;
    }

    /**
     * Create a new loan.
     *
     * @param array $loanData
     * @return Loan
     * @throws GraphqlError
     */
    public function create(array $loanData): Loan
    {
        // Check to ensure that a user can only have one active loan at a time
        $user = $this->userRepository->find($loanData['user_id']);
        if (count($user->activeLoans()) > 0) {
            throw new GraphqlError('This user already has an active loan and cannot take a new loan');
        }

        // We need to generate a unique (app specified) identifier for each loan
        $loanData['loan_identifier'] = $this->generateLoanIdentifier();

        // Ensure that the default values when creating a loan are set
        $loanData['disbursement_status'] = DisbursementStatus::NOT_DISBURSED;
        $loanData['application_status'] = LoanApplicationStatus::PENDING;
        $loanData['loan_condition_status'] = LoanConditionStatus::INACTIVE;
        $loanData['loan_default_status'] = LoanDefaultStatus::NOT_DEFAULTING;
        $loanData['disbursement_date'] = null;
        $loanData['amount_disbursed'] = 0;
        $loanData['num_of_default_days'] = null;
        $loanData['loan_balance'] = null;
        $loanData['next_due_payment'] = null;
        $loanData['due_date'] = null;

        $loan = $this->loanRepository->create($loanData);

        event(new NewLoanCreated($loan));

        return $loan;
    }

    /**
     * Generate a random loan identifier for a loan.
     *
     * @return int
     */
    private function generateLoanIdentifier(): int
    {
        $identifier = mt_rand(1000000000, 9999999999); // better than rand()

        // call the same function if the loan_identifier exists already
        if ($this->loanRepository->loanIdentifierExists($identifier)) {
            return self::generateLoanIdentifier();
        }

        return $identifier;
    }

    /**
     * Update the application_state of a loan.
     *
     * @param string $loanID
     * @param string $loanApplicationStatus
     * @param null|string $message
     * @return Loan
     */
    public function updateLoanApplicationStatus(string $loanID, string $loanApplicationStatus, ?string $message)
    {
        $loan = $this->loanRepository->find($loanID);
        $oldLoanApplicationStatus = $loan->application_status;

        $this->loanRepository->updateApplicationState($loan, $loanApplicationStatus);

        event(new LoanApplicationStatusChanged($loan, $oldLoanApplicationStatus, Auth::user(), $message));
        return $loan;
    }

    /**
     * Disburse a loan
     *
     * @param string $loanID
     * @param float $amountDisbursed
     * @param null|string $message
     * @return
     * @throws \Exception
     */
    public function disburseLoan(string $loanID, float $amountDisbursed, ?string $message) {
        $loan = $this->loanRepository->find($loanID);
        $loanBalance = $loan->loan_balance;

        if($loan->disbursement_status === DisbursementStatus::DISBURSED) {
            throw new GraphqlError("Cannot disburse funds for a loan that has already been disbursed");
        }

        if($loan->application_status !== LoanApplicationStatus::APPROVED_BY_GLOBAL_MANAGER()->getValue()) {
            throw new GraphqlError("Cannot disburse funds for a loan that is not approved");
        }

        $this->loanRepository->disburseLoan($loan, $amountDisbursed);

        event(new LoanDisbursed($loan, $amountDisbursed, $message));

        return $loan;
    }

    /**
     * Repay a loan
     *
     * @param string $loan_id
     * @param array $transactionDetails
     * @return \App\Models\Transaction
     * @throws GraphqlError
     */
    public function initiateLoanRepayment(string $loan_id, array $transactionDetails) {
        $loan = $this->loanRepository->find($loan_id);

        $transactionAmount = $transactionDetails['transaction_amount'];

        if($transactionDetails['transaction_amount'] > $loan->loan_balance) {
            throw new GraphqlError("Transaction amount {$transactionAmount} is greater than the total loan balance");
        }

        if($transactionDetails['transaction_type'] !== TransactionType::LOAN_REPAYMENT) {
            throw new GraphqlError("The transaction type selected must be Loan Repayment");
        }

        $transaction = $this->transactionService->initiateLoanRepaymentTransaction($loan, $transactionDetails);

        return $transaction;
    }

}
