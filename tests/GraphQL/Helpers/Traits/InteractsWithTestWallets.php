<?php

namespace Tests\GraphQL\Helpers\Traits;


use App\Models\Wallet;
use App\Models\Enums\TransactionType;
use App\Models\Transaction;

trait InteractsWithTestWallets
{
    use InteractsWithTestUsers;

    /**
     * Create a test wallet Transaction.
     *
     * @param string $transactionType
     * @param array|null $userRoles
     * @return array
     */
    public function createWalletAndTransactionData(string $transactionType, array $userRoles = null)
    {
        if ($userRoles) {
            $this->loginTestUserAndGetAuthHeaders($userRoles);
        } else {
            $this->loginTestUserAndGetAuthHeaders();
        }

        $wallet = factory(Wallet::class)->create([
            'id' => $this->faker->uuid,
            'user_id' => $this->user['id'],
            'wallet_balance' => 1000,
        ]);

        $transactionDetails = factory(Transaction::class)->make([
            'transaction_amount' => 500,
            'transaction_type' => $transactionType,
        ])->toArray();

        $transactionData = [
            'owner_id' => $wallet->id,
            'transaction_details' => $transactionDetails
        ];

        return [
            'wallet' => $wallet,
            'transactionDetails' => $transactionDetails,
            'transactionData' => $transactionData,
        ];
    }

    /**
     * Create a test wallet.
     *
     * @param null $user
     * @param null $numberofWallets
     * @return mixed
     */
    public function createTestWallet($user = null, $numberofWallets = null)
    {
        if (!$user) {
            $user = $this->createUser();
        }

        if (!$numberofWallets) {
            return factory(Wallet::class)->create([
                'user_id' => $user->id
            ]);
        }

        return factory(Wallet::class, $numberofWallets)->create([
            'user_id' => $user->id
        ]);
    }
}
