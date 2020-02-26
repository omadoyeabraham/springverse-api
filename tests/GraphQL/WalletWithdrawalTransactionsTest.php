<?php

namespace Tests\GraphQL;

use App\Models\Wallet;
use App\Models\enums\TransactionOwnerType;
use App\Models\enums\TransactionProcessingActions;
use App\Models\enums\TransactionStatus;
use App\Models\enums\TransactionType;
use App\Models\Enums\UserRoles;
use App\Models\ProcessedTransaction;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\GraphQL\Helpers\Schema\TransactionsQueriesAndMutations;
use Tests\GraphQL\Helpers\Traits\InteractsWithTestWallets;
use Tests\GraphQL\Helpers\Traits\InteractsWithTestUsers;
use Tests\TestCase;

class WalletWithdrawalTransactionsTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestUsers, InteractsWithTestWallets, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed('TestDatabaseSeeder');
    }

    /**
     * @test
     */
    public function testItInitiatesWalletWithdrawalTransactionSuccessfully()
    {
        $walletData = $this->createWalletAndTransactionData(TransactionType::WALLET_WITHDRAWAL);
        $wallet = $walletData['wallet'];
        $transactionDetails = $walletData['transactionDetails'];
        $transactionData = $walletData['transactionData'];

        $response = $this->postGraphQL([
            'query' => TransactionsQueriesAndMutations::initiateTransaction(),
            'variables' => [
                'input' => $transactionData
            ],
        ], $this->headers);

        $response->assertJson([
            'data' => [
                'InitiateTransaction' => [
                    'transaction_amount' => 500,
                ]
            ]
        ]);

        $this->assertDatabaseHas(with(new Transaction)->getTable(), [
            'owner_type' => TransactionOwnerType::WALLET,
            'owner_id' => $wallet->id,
            'transaction_amount' => $transactionDetails['transaction_amount'],
            'transaction_type' => $transactionDetails['transaction_type'],
            'transaction_purpose' => $transactionDetails['transaction_purpose'],
        ]);
    }

    /**
     * @test
     */
    public function testItCorrectlyApprovesAWalletWithdrawalTransaction()
    {
        $this->loginTestUserAndGetAuthHeaders([UserRoles::BRANCH_ACCOUNTANT]);

        $wallet = factory(Wallet::class)->create([
            'id' => $this->faker->uuid,
            'user_id' => $this->user['id'],
            'wallet_amount' => 2000,
            'wallet_balance' => 1000,
        ]);

        $transaction = factory(Transaction::class)->create([
            'transaction_amount' => 500,
            'transaction_type' => TransactionType::WALLET_WITHDRAWAL,
            'transaction_status' => TransactionStatus::PENDING,
            'owner_id' => $wallet->id,
            'owner_type' => TransactionOwnerType::WALLET
        ]);

        $message = $this->faker->realText();

        $response = $this->postGraphQL([
            'query' => TransactionsQueriesAndMutations::processTransaction(),
            'variables' => [
                'transaction_id' => $transaction->id,
                'action' => TransactionProcessingActions::APPROVE,
                'message' => $message,
            ],
        ], $this->headers);

        $response->assertJson([
            'data' => [
                'ProcessTransaction' => [
                    'id' => $transaction->id,
                    'transaction_amount' => 500,
                    'transaction_status' => TransactionStatus::COMPLETED
                ]
            ]
        ]);

        $this->assertDatabaseHas(with(new Transaction)->getTable(), [
            'id' => $transaction->id,
            'transaction_status' => TransactionStatus::COMPLETED,
            'transaction_amount' => $transaction->transaction_amount,
            'transaction_type' => $transaction->transaction_type,
            'transaction_purpose' => $transaction->transaction_purpose,
        ]);

        $this->assertDatabaseHas(with(new ProcessedTransaction())->getTable(), [
            'causer_id' => $this->user['id'],
            'transaction_id' => $transaction->id,
            'processing_type' => TransactionProcessingActions::APPROVE,
            'message' => $message
        ]);
    }

    /**
     * @test
     * @group active
     */
    public function testItCorrectlyDisapprovesAWalletWithdrawalTransaction()
    {
        $this->loginTestUserAndGetAuthHeaders([UserRoles::BRANCH_ACCOUNTANT]);

        /**
         * Create a wallet
         * Create a wallet transaction for the loan (ensure it's pending)
         * Try to approve it, ensure that it's approved
         */

        $wallet = factory(Wallet::class)->create([
            'id' => $this->faker->uuid,
            'user_id' => $this->user['id'],
            'wallet_amount' => 2000,
            'wallet_balance' => 1000,
        ]);

        $transaction = factory(Transaction::class)->create([
            'transaction_amount' => 500,
            'transaction_type' => TransactionType::WALLET_WITHDRAWAL,
            'transaction_status' => TransactionStatus::PENDING,
            'owner_id' => $wallet->id,
            'owner_type' => TransactionOwnerType::WALLET
        ]);
        $message = $this->faker->realText();

        $response = $this->postGraphQL([
            'query' => TransactionsQueriesAndMutations::processTransaction(),
            'variables' => [
                'transaction_id' => $transaction->id,
                'action' => TransactionProcessingActions::DISAPPROVE,
                'message' => $message,
            ],
        ], $this->headers);

        $response->assertJson([
            'data' => [
                'ProcessTransaction' => [
                    'id' => $transaction->id,
                    'transaction_amount' => 500,
                    'transaction_status' => TransactionStatus::FAILED
                ]
            ]
        ]);

        $this->assertDatabaseHas(with(new Transaction)->getTable(), [
            'id' => $transaction->id,
            'transaction_status' => TransactionStatus::FAILED,
            'transaction_amount' => $transaction->transaction_amount,
            'transaction_type' => $transaction->transaction_type,
            'transaction_purpose' => $transaction->transaction_purpose,
        ]);

        $this->assertDatabaseHas(with(new ProcessedTransaction())->getTable(), [
            'causer_id' => $this->user['id'],
            'transaction_id' => $transaction->id,
            'processing_type' => TransactionProcessingActions::DISAPPROVE,
            'message' => $message
        ]);
    }
}
