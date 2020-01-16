<?php

namespace Tests\Feature;

use App\Models\Enums\DisbursementStatus;
use App\Models\Enums\LoanApplicationStatus;
use App\Models\Enums\LoanConditionStatus;
use App\Models\Enums\LoanDefaultStatus;
use App\Models\Enums\UserRoles;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\GraphQL\Helpers\Schema\LoanQueriesAndMutations;
use Tests\GraphQL\Helpers\Traits\InteractsWIthTestLoans;
use Tests\GraphQL\Helpers\Traits\InteractsWithTestUsers;
use Tests\TestCase;

class LoanMutationsTest extends TestCase
{
    use RefreshDatabase, InteractsWithTestUsers, InteractsWIthTestLoans;

    /**
     * @var User
     */
    private $user;

    /**
     * @var array
     */
    private $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed('DatabaseSeeder');
    }

    /**
     * @test
     */
    public function testItSuccessfullyCreatesANewLoan()
    {
        $testUserDetails = $this->createLoginAndGetTestUserDetails([UserRoles::ADMIN_STAFF]);
        $this->user = $testUserDetails['user'];
        $accessToken = $testUserDetails['access_token'];
        $this->headers = $this->getGraphQLAuthHeader($accessToken);

        $loanData = collect(factory(Loan::class)->state('not_disbursed_loan')->make())
            ->except(['loan_identifier'])
            ->toArray();
        $loanData['user_id'] = $this->user['id'];

        $response = $this->postGraphQL([
            'query' => LoanQueriesAndMutations::CreateLoanMutation(),
            'variables' => [
                'input' => $loanData
            ],
        ], $this->headers);

        $response->assertJson([
            'data' => [
                'CreateLoan' => [
                    'loan_amount' => $loanData['loan_amount']
                ]
            ]
        ]);

        $createdLoan = $response->json("data.CreateLoan");

        $this->assertEquals(DisbursementStatus::NOT_DISBURSED, $createdLoan['disbursement_status']);
        $this->assertEquals(LoanApplicationStatus::PENDING, $createdLoan['application_status']);
        $this->assertEquals(LoanConditionStatus::INACTIVE, $createdLoan['loan_condition_status']);
        $this->assertEquals(LoanDefaultStatus::NOT_DEFAULTING, $createdLoan['loan_default_status']);
    }

    /**
     * @test
     */
    public function testItCanUpdateTheLoanApplicationStatus() {
        $testUserDetails = $this->createLoginAndGetTestUserDetails([UserRoles::BRANCH_MANAGER]);
        $this->user = $testUserDetails['user'];
        $accessToken = $testUserDetails['access_token'];
        $this->headers = $this->getGraphQLAuthHeader($accessToken);

        $loan = $this->createTestLoan();

        $response = $this->postGraphQL([
           'query' => LoanQueriesAndMutations::UpdateLoanApplicationStatus(),
            'variables' => [
                'loan_id' => $loan->id,
                'application_status' => LoanApplicationStatus::APPROVED_BY_BRANCH_MANAGER(),
                'message' => "The loan looks good and viable"
            ]
        ], $this->headers);

        $response->assertJson([
            'data' => [
                'UpdateLoanApplicationStatus' => [
                    'application_status' => LoanApplicationStatus::APPROVED_BY_BRANCH_MANAGER()
                ]
            ]
        ]);
    }

}