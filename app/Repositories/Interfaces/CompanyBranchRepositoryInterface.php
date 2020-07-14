<?php

namespace App\Repositories\Interfaces;


use App\Models\CompanyBranch;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

interface CompanyBranchRepositoryInterface
{
    /**
     * Find a CompanyBranch by id.
     *
     * @param string $branch_id
     * @return CompanyBranch|null
     */
    public function find(string $branch_id): ?CompanyBranch;

    /**
     * Find the customers that belong to a branch.
     *
     * @param string $branch_id
     * @return Collection
     */
    public function findCustomers(string $branch_id): Collection;

    /**
     * Get the eloquent query builder that can get customers that belong to a branch.
     *
     * @param string $branch_id
     * @return HasManyThrough
     */
    public function findCustomersQuery(string $branch_id): HasManyThrough;

    /**
     * Get the eloquent query builder that can get admins that belong to a branch.
     *
     * @param string     $branch_id
     * @param array|null $roles
     * @return HasManyThrough
     */
    public function findAdminsQuery(string $branch_id, ?array $roles): HasManyThrough;

    /**
     * Find the loans that belong to a branch.
     *
     * @param string $branch_id
     * @return Collection
     */
    public function findLoans(string $branch_id): Collection;

    /**
     * Get the eloquent query builder that can get loans that belong to a branch.
     *
     * @param string $branch_id
     * @return HasManyThrough
     */
    public function findLoansQuery(string $branch_id): HasManyThrough;

    /**
     * Get the eloquent query builder that can get contribution plans that belong to a branch.
     *
     * @param string $branch_id
     * @return HasManyThrough
     */
    public function findContributionPlansQuery(string $branch_id): HasManyThrough;


    /**
     * Get the eloquent query builder that can get loan applications that belong to a branch.
     *
     * @param string      $branch_id
     * @param null|string $isAssigned
     * @return HasManyThrough
     */
    public function findLoanApplicationsQuery(string $branch_id, ?string $isAssigned): HasManyThrough;

    /**
     * Search/Filter the customers for a branch.
     *
     * @param string $branch_id
     * @param null|string $search_query
     * @param Date|null $start_date
     * @param Date|null $end_date
     * @return HasManyThrough
     */
    public function searchBranchCustomers(string $branch_id, ?string $search_query, ?Date $start_date, ?Date $end_date): HasManyThrough;

    /**
     * Get the eloquent query builder that can get transactions that belong to a branch.
     *
     * @param string $branch_id
     * @return mixed
     */
    public function findTransactionsQuery(string $branch_id);
}
