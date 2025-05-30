<?php

namespace App\Core;

use Exception;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

abstract class BaseController
{

    private static Environment $twig;
    private static string $scriptAssets;
    private static array $styleAssets;
    private static string $hmrClient;

    /**
     * @throws Exception
     */
    public static function init(): void
    {
        $loader = new FilesystemLoader(BASE_PATH . '/templates');
        if (DEV) {
            self::$twig = new Environment($loader);
        } else {
            self::$twig = new Environment($loader, [
                'cache' => BASE_PATH . '/twig_cache', // Optional: Configure a cache directory for production
            ]);
        }

        // Make session data available in all templates
        self::$twig->addGlobal('session', $_SESSION);

        self::$scriptAssets = ViteAssets::asset("main.ts");
        self::$styleAssets = ViteAssets::cssTags('main.ts');
        self::$hmrClient = ViteAssets::HMRClient();

    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    protected function render(string $viewName, array $data = []): void
    {

        $template = self::$twig->load("$viewName.html.twig");

        echo $template->render(["assets" => self::$scriptAssets, "styleAssets" => self::$styleAssets, "hmrClient" => self::$hmrClient] + $data);
    }

}

