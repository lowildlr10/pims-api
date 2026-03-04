<?php

namespace App\Providers;

use App\Interfaces\AbstractQuotationRepositoryInterface;
use App\Interfaces\AccountClassificationRepositoryInterface;
use App\Interfaces\AccountRepositoryInterface;
use App\Interfaces\BidsAwardsCommitteeRepositoryInterface;
use App\Interfaces\CompanyRepositoryInterface;
use App\Interfaces\DeliveryTermRepositoryInterface;
use App\Interfaces\DepartmentRepositoryInterface;
use App\Interfaces\DesignationRepositoryInterface;
use App\Interfaces\DisbursementVoucherInterface;
use App\Interfaces\FunctionProgramProjectRepositoryInterface;
use App\Interfaces\FundingSourceRepositoryInterface;
use App\Interfaces\InspectionAcceptanceReportInterface;
use App\Interfaces\InventoryIssuanceRepositoryInterface;
use App\Interfaces\InventorySupplyInterface;
use App\Interfaces\ItemClassificationRepositoryInterface;
use App\Interfaces\LocationRepositoryInterface;
use App\Interfaces\LogRepositoryInterface;
use App\Interfaces\MediaRepositoryInterface;
use App\Interfaces\NotificationRepositoryInterface;
use App\Interfaces\ObligationRequestInterface;
use App\Interfaces\PaperSizeRepositoryInterface;
use App\Interfaces\PaymentTermRepositoryInterface;
use App\Interfaces\PositionRepositoryInterface;
use App\Interfaces\ProcurementModeRepositoryInterface;
use App\Interfaces\PurchaseOrderRepositoryInterface;
use App\Interfaces\PurchaseRequestRepositoryInterface;
use App\Interfaces\RequestQuotationRepositoryInterface;
use App\Interfaces\ResponsibilityCenterRepositoryInterface;
use App\Interfaces\RoleRepositoryInterface;
use App\Interfaces\SectionRepositoryInterface;
use App\Interfaces\SignatoryRepositoryInterface;
use App\Interfaces\SupplierRepositoryInterface;
use App\Interfaces\TaxWithholdingRepositoryInterface;
use App\Interfaces\UnitIssueRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\PersonalAccessToken;
use App\Repositories\AbstractQuotationRepository;
use App\Repositories\AccountClassificationRepository;
use App\Repositories\AccountRepository;
use App\Repositories\BidsAwardsCommitteeRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\DeliveryTermRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\DesignationRepository;
use App\Repositories\DisbursementVoucherRepository;
use App\Repositories\FunctionProgramProjectRepository;
use App\Repositories\FundingSourceRepository;
use App\Repositories\InspectionAcceptanceReportRepository;
use App\Repositories\InventoryIssuanceRepository;
use App\Repositories\InventorySupplyRepository;
use App\Repositories\ItemClassificationRepository;
use App\Repositories\LocationRepository;
use App\Repositories\LogRepository;
use App\Repositories\MediaRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\ObligationRequestRepository;
use App\Repositories\PaperSizeRepository;
use App\Repositories\PaymentTermRepository;
use App\Repositories\PositionRepository;
use App\Repositories\ProcurementModeRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\PurchaseRequestRepository;
use App\Repositories\RequestQuotationRepository;
use App\Repositories\ResponsibilityCenterRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SectionRepository;
use App\Repositories\SignatoryRepository;
use App\Repositories\SupplierRepository;
use App\Repositories\TaxWithholdingRepository;
use App\Repositories\UnitIssueRepository;
use App\Repositories\UserRepository;
use App\Services\AbstractQuotationService;
use App\Services\AccountClassificationService;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\BidsAwardsCommitteeService;
use App\Services\CompanyService;
use App\Services\DashboardService;
use App\Services\DeliveryTermService;
use App\Services\DepartmentService;
use App\Services\DesignationService;
use App\Services\DisbursementVoucherService;
use App\Services\FunctionProgramProjectService;
use App\Services\FundingSourceService;
use App\Services\InspectionAcceptanceReportService;
use App\Services\InventoryIssuanceService;
use App\Services\InventorySupplyService;
use App\Services\ItemClassificationService;
use App\Services\LocationService;
use App\Services\LogService;
use App\Services\NotificationService;
use App\Services\ObligationRequestService;
use App\Services\PaperSizeService;
use App\Services\PaymentTermService;
use App\Services\PositionService;
use App\Services\ProcurementModeService;
use App\Services\PurchaseOrderService;
use App\Services\PurchaseRequestService;
use App\Services\RequestQuotationService;
use App\Services\ResponsibilityCenterService;
use App\Services\RoleService;
use App\Services\SectionService;
use App\Services\SignatoryService;
use App\Services\SupplierService;
use App\Services\TaxWithholdingService;
use App\Services\UnitIssueService;
use App\Services\UserService;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LocationRepositoryInterface::class, LocationRepository::class);
        $this->app->bind(LocationService::class);

        $this->app->bind(NotificationService::class);

        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(DepartmentService::class);

        $this->app->bind(SectionRepositoryInterface::class, SectionRepository::class);
        $this->app->bind(SectionService::class);

        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(RoleService::class);

        $this->app->bind(DesignationRepositoryInterface::class, DesignationRepository::class);
        $this->app->bind(DashboardService::class);

        $this->app->bind(DesignationService::class);

        $this->app->bind(PositionRepositoryInterface::class, PositionRepository::class);
        $this->app->bind(PositionService::class);

        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserService::class);

        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(CompanyService::class);

        $this->app->bind(AccountClassificationRepositoryInterface::class, AccountClassificationRepository::class);
        $this->app->bind(AccountClassificationService::class);

        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->bind(AccountService::class);

        $this->app->bind(BidsAwardsCommitteeRepositoryInterface::class, BidsAwardsCommitteeRepository::class);
        $this->app->bind(BidsAwardsCommitteeService::class);

        $this->app->bind(DeliveryTermRepositoryInterface::class, DeliveryTermRepository::class);
        $this->app->bind(DeliveryTermService::class);

        $this->app->bind(FunctionProgramProjectRepositoryInterface::class, FunctionProgramProjectRepository::class);
        $this->app->bind(FunctionProgramProjectService::class);

        $this->app->bind(FundingSourceRepositoryInterface::class, FundingSourceRepository::class);
        $this->app->bind(FundingSourceService::class);

        $this->app->bind(PaperSizeRepositoryInterface::class, PaperSizeRepository::class);
        $this->app->bind(PaperSizeService::class);

        $this->app->bind(ItemClassificationRepositoryInterface::class, ItemClassificationRepository::class);
        $this->app->bind(ItemClassificationService::class);

        $this->app->bind(LogRepositoryInterface::class, LogRepository::class);
        $this->app->bind(LogService::class);

        $this->app->bind(PaymentTermRepositoryInterface::class, PaymentTermRepository::class);
        $this->app->bind(PaymentTermService::class);

        $this->app->bind(ProcurementModeRepositoryInterface::class, ProcurementModeRepository::class);
        $this->app->bind(ProcurementModeService::class);

        $this->app->bind(ResponsibilityCenterRepositoryInterface::class, ResponsibilityCenterRepository::class);
        $this->app->bind(ResponsibilityCenterService::class);

        $this->app->bind(SignatoryRepositoryInterface::class, SignatoryRepository::class);
        $this->app->bind(SignatoryService::class);

        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
        $this->app->bind(SupplierService::class);

        $this->app->bind(UnitIssueRepositoryInterface::class, UnitIssueRepository::class);
        $this->app->bind(UnitIssueService::class);

        $this->app->bind(AuthService::class);

        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(NotificationService::class);

        $this->app->bind(PurchaseRequestRepositoryInterface::class, PurchaseRequestRepository::class);
        $this->app->bind(PurchaseRequestService::class);

        $this->app->bind(RequestQuotationRepositoryInterface::class, RequestQuotationRepository::class);
        $this->app->bind(RequestQuotationService::class);

        $this->app->bind(AbstractQuotationRepositoryInterface::class, AbstractQuotationRepository::class);
        $this->app->bind(AbstractQuotationService::class);

        $this->app->bind(PurchaseOrderRepositoryInterface::class, PurchaseOrderRepository::class);
        $this->app->bind(PurchaseOrderService::class);

        $this->app->bind(ObligationRequestInterface::class, ObligationRequestRepository::class);
        $this->app->bind(ObligationRequestService::class);

        $this->app->bind(InspectionAcceptanceReportInterface::class, InspectionAcceptanceReportRepository::class);
        $this->app->bind(InspectionAcceptanceReportService::class);

        $this->app->bind(DisbursementVoucherInterface::class, DisbursementVoucherRepository::class);
        $this->app->bind(DisbursementVoucherService::class);

        $this->app->bind(InventorySupplyInterface::class, InventorySupplyRepository::class);
        $this->app->bind(InventorySupplyService::class);

        $this->app->bind(InventoryIssuanceRepositoryInterface::class, InventoryIssuanceRepository::class);
        $this->app->bind(InventoryIssuanceService::class);

        $this->app->bind(MediaRepositoryInterface::class, MediaRepository::class);

        $this->app->bind(TaxWithholdingRepositoryInterface::class, TaxWithholdingRepository::class);
        $this->app->bind(TaxWithholdingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(UrlGenerator $url): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        if (env('APP_ENV') === 'production') {
            $url->forceScheme('https');
        }
    }
}
