<?php
namespace REDEASEDD\RedeemerAppSumoEDD;

use REDEASEDD\RedeemerAppSumoEDD\Admin\SettingsPage;
use REDEASEDD\RedeemerAppSumoEDD\Controllers\RedeemController;
use REDEASEDD\RedeemerAppSumoEDD\Controllers\AjaxController;
use REDEASEDD\RedeemerAppSumoEDD\Frontend\Shortcode;
use REDEASEDD\RedeemerAppSumoEDD\Infrastructure\Options;
use REDEASEDD\RedeemerAppSumoEDD\Services\LocalStoreVerifier;
use REDEASEDD\RedeemerAppSumoEDD\Services\EDDService;
use REDEASEDD\RedeemerAppSumoEDD\Admin\DashboardWidget;

class Plugin
{
    public function boot(): void
    {
        add_action('admin_init', [ SettingsPage::class, 'register' ]);
        add_action('admin_menu', [ SettingsPage::class, 'menu' ]);

        // REST route (server-to-server or Zapier etc.)
        add_action('rest_api_init', function () {
            $controller = new RedeemController(
                new Options(),
                new LocalStoreVerifier(new Options()),
                new EDDService()
            );
            $controller->register_routes();
        });

        // Frontend shortcode + AJAX (safe form, no secret exposed)
        Shortcode::register();
        (new AjaxController(
            new Options(),
            new LocalStoreVerifier(new Options()),
            new EDDService()
        ))->register();
        DashboardWidget::register();
    }
}
