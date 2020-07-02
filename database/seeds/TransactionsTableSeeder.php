<?php

use App\Models\CompanyBranch;
use App\Models\ContributionPlan;
use App\Models\enums\TransactionOwnerType;
use App\Models\enums\TransactionStatus;
use App\Models\enums\TransactionType;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class TransactionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedLoanTransactions();
        $this->seedContributionTransactions();
        $this->seedWalletTransactions();
    }

    private function seedLoanTransactions()
    {
        $loans = Loan::limit(5)->get();
        $branches = CompanyBranch::all()->toArray();

        foreach ($loans as $loan) {
            $transactionTypes = [
                TransactionType::LOAN_DISBURSEMENT,
                TransactionType::LOAN_REPAYMENT
            ];

            $transactionStatustes = [
                TransactionStatus::PENDING,
                TransactionStatus::COMPLETED,
                TransactionStatus::FAILED
            ];

            factory(Transaction::class, 5)->create([
                'transaction_type' => $transactionTypes[array_rand($transactionTypes)],
                'owner_id' => $loan->id,
                'owner_type' => TransactionOwnerType::LOAN,
                'transaction_status' => $transactionStatustes[array_rand($transactionStatustes)],
                'branch_id' => $branches[array_rand($branches)]['id']
            ]);
        }
    }

    private function seedContributionTransactions()
    {
        $contributionPlans = ContributionPlan::limit(5)->get();
        $branches = CompanyBranch::all()->toArray();

        foreach ($contributionPlans as $contributionPlan) {
            $transactionTypes = [
                TransactionType::CONTRIBUTION_PAYMENT
            ];

            $transactionStatustes = [
                TransactionStatus::PENDING,
                TransactionStatus::COMPLETED,
                TransactionStatus::FAILED
            ];

            factory(Transaction::class, 5)->create([
                'transaction_type' => $transactionTypes[array_rand($transactionTypes)],
                'owner_id' => $contributionPlan->id,
                'owner_type' => TransactionOwnerType::CONTRIBUTION_PLAN,
                'transaction_status' => $transactionStatustes[array_rand($transactionStatustes)],
                'branch_id' => $branches[array_rand($branches)]['id']
            ]);
        }
    }

    private function seedWalletTransactions ()
    {
        $wallets = Wallet::limit(5)->get();
        $branches = CompanyBranch::all()->toArray();

        foreach ($wallets as $wallet) {
            $transactionTypes = [
                TransactionType::WALLET_PAYMENT,
                TransactionType::WALLET_WITHDRAWAL
            ];

            $transactionStatustes = [
                TransactionStatus::PENDING,
                TransactionStatus::COMPLETED,
                TransactionStatus::FAILED
            ];


            factory(Transaction::class, 5)->create([
                'transaction_type' => $transactionTypes[array_rand($transactionTypes)],
                'owner_id' => $wallet->id,
                'owner_type' => TransactionOwnerType::WALLET,
                'transaction_status' => $transactionStatustes[array_rand($transactionStatustes)],
                'branch_id' => $branches[array_rand($branches)]['id']
            ]);
    }
}
}
