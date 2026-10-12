<?php

namespace App\Interface;

interface CompanyInfoInterface
{
    public function getAllCompanyInfo(): array;

    public function getCompanyInfoById(int $id): array;

    public function createCompanyInfo(array $data): array;

    public function updateCompanyInfo(int $id, array $data): array;

    public function deleteCompanyInfo(int $id): bool;

    /**
     * The one record currently marked active, formatted for display and
     * cached — this is what the shared Inertia `companyInfo` prop reads.
     * Null if no row is active yet (e.g. a fresh install before seeding).
     */
    public function getActiveCompanyInfo(): ?array;
}
