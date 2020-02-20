<?php

namespace Tests\GraphQL\Helpers\Schema;


class CustomerWithdrawalRequestQueriesAndMutations
{
    /**
     * Mutation for creating a CustomerWithdrawalRequest
     *
     * @return string
     */
    public static function createCustomerWithdrawalRequest()
    {
        return '
            mutation CreateCustomerWithdrawalRequest($input: CreateCustomerWithdrawalRequestInput!) {
                CreateCustomerWithdrawalRequest(input: $input) {
                    user_id
                    request_amount
                    request_status
                    request_type
                    request_balance  
                }
            }
        ';
    }

    /**
     * Mutation for updating a CustomerWithdrawalRequest
     *
     * @return string
     */
    public static function updateCustomerWithdrawalRequest()
    {
        return '
            mutation UpdateCustomerWithdrawalRequest($input: UpdateCustomerWithdrawalRequestInput!) {
                UpdateCustomerWithdrawalRequest(input: $input) {
                    id
                    request_type
                    request_amount
                    request_status
                }
            }
        ';
    }

    /**
     * Mutation for deleting a CustomerWithdrawalRequest
     *
     * @return string
     */
    public static function deleteCustomerWithdrawalRequest()
    {
        return '
            mutation DeleteCustomerWithdrawalRequest($user_id: ID!) {
                DeleteCustomerWithdrawalRequest(CustomerWithdrawalRequest_id: $CustomerWithdrawalRequest_id) {
                    id
                    request_type
                    request_amount
                    request_status
                }
            }
        ';
    }
}
