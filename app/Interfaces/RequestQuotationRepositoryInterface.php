<?php

namespace App\Interfaces;

interface RequestQuotationRepositoryInterface
{
    public function print(array $pageConfig, string $prId);
}
