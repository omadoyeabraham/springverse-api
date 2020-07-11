<?php

namespace App\Repositories;


use App\Models\CompanyBranch;
use App\Models\Enums\UserRoles;
use App\Repositories\Interfaces\CompanyBranchRepositoryInterface;
use App\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class CompanyBranchRepository implements CompanyBranchRepositoryInterface
{

    /**
     * Find a CompanyBranch by id.
     *
     * @param string $branch_id
     * @return CompanyBranch|null
     */
    public function find(string $branch_id): ?CompanyBranch
    {
        return CompanyBranch::findOrFail($branch_id);
    }

    /**
     * Find the customers that belong to a branch.
     *
     * @param string $branch_id
     * @return Collection
     */
    public function findCustomers(string $branch_id): Collection
    {
        $branch = $this->find($branch_id);

        return $branch->customers()->role([UserRoles::CUSTOMER])->get();
    }


    /**
     * Get the eloquent query builder that can get customers that belong to a branch.
     *
     * @param string $branch_id
     * @return HasManyThrough
     */
    public function findCustomersQuery(string $branch_id): HasManyThrough
    {
        $branch = $this->find($branch_id);

        return $branch->customers()->role([UserRoles::CUSTOMER]);
    }

    /**
     * Get the eloquent query builder that can get admins that belong to a branch.
     *
     * @param string $branch_id
     * @return HasManyThrough
     */
    public function findAdminsQuery(string $branch_id): HasManyThrough
    {
        $branch = $this->find($branch_id);

        return $branch->customers()->role([UserRoles::ADMIN_STAFF, UserRoles::BRANCH_ACCOUNTANT, UserRoles::BRANCH_MANAGER,
            UserRoles::ADMIN_MANAGER, UserRoles::ADMIN_ACCOUNTANT]);
    }

    /**
     * Find the loans that belong to a branch.
     *
     * @param string $branch_id
     * @return Collection
     */
    public function findLoans(string $branch_id): Collection
    {
        $branch = $this->find($branch_id);

        return $branch->loans()->get();
    }

    /**
     * Get the eloquent query builder that can get loans that belong to a branch.
     *
     * @param string $branch_id
     * @return HasManyThrough
     */
    public function findLoansQuery(string $branch_id): HasManyThrough
    {
        $branch = $this->find($branch_id);

        return $branch->loans();
    }

    /**
     * Get the eloquent query builder that can get loan applications that belong to a branch.
     *
     * @param string      $branch_id
     * @param null|string $isAssigned
     * @return HasManyThrough
     */
    public function findLoanApplicationsQuery(string $branch_id, ?string $isAssigned): HasManyThrough
    {
        $branch = $this->find($branch_id);
        $query  = $branch->loanApplications();

        if (isset($isAssigned)) {
            if ($isAssigned) {
                $query->whereNotNull('loan_applications.assignee_id');
            } else {
                $query->whereNull('loan_applications.assignee_id');
            }
        }

        return $query;
    }

    /**
     * Search/Filter the customers for a branch.
     *
     * @param string      $branch_id
     * @param null|string $search_query
     * @param Date|null   $start_date
     * @param Date|null   $end_date
     * @return HasManyThrough
     */
    public function searchBranchCustomers(string $branch_id, ?string $search_query, ?Date $start_date, ?Date $end_date): HasManyThrough
    {
        $branch = $this->find($branch_id);

        return $branch->customers()->role([UserRoles::CUSTOMER])->where(function ($query) use ($search_query, $start_date, $end_date) {


            if (isset($search_query)) {
                $query->where(DB::raw('lower(users.first_name)'), 'like', "%{$search_query}%")
                    ->orWhere(DB::raw('lower(users.last_name)'), 'like', "%{$search_query}%");
            }

            if (isset($start_date)) {
                $query->whereDate('created_at', '>=', $start_date);
            }

            if (isset($end_date)) {
                $query->whereDate('created_at', '<=', $end_date);
            }

        });
    }

    /**
     * Get the eloquent query builder that can get transactions that belong to a branch.
     *
     * @param string $branch_id
     * @param array  $queryParameters
     * @return mixed
     */
    public function findTransactionsQuery(string $branch_id, array $queryParameters = [])
    {
        $branch = $this->find($branch_id);

        return $branch->transactions()->where(function ($query) use ($queryParameters) {

            if (isset($queryParameters['min_amount'])) {
                $query->where('transaction_amount', '>=', floatval($queryParameters['min_amount']));
            }

            if (isset($queryParameters['max_amount'])) {
                $query->where('transaction_amount', '<=', floatval($queryParameters['max_amount']));
            }

            if (isset($queryParameters['start_date'])) {
                $query->whereDate('created_at', '>=', $queryParameters['start_date']);
            }

            if (isset($queryParameters['end_date'])) {
                $query->whereDate('created_at', '<=', $queryParameters['end_date']);
            }

        });
    }
}
