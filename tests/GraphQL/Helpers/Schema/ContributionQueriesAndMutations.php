<?php

namespace Tests\GraphQL\Helpers\Schema;


class ContributionQueriesAndMutations
{
    /**
     * Mutation for creating a contribution
     *
     * @return string
     */
    public static function createContribution() {
        return '
            mutation CreateContribution($input: CreateContributionInput!) {
                CreateContribution(input: $input) {
                    id
                    contribution_type
                    contribution_amount
                    contribution_frequency
                }
            }
        ';
    }

    /**
     * Mutation for updating a contribution
     *
     * @return string
     */
    public static function updateContribution() {
        return '
            mutation UpdateContribution($input: UpdateContributionInput!) {
                UpdateContribution(input: $input) {
                    id
                    contribution_type
                    contribution_amount
                    contribution_frequency
                }
            }
        ';
    }
}
