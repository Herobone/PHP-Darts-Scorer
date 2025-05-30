<?php

namespace App\Controller;

use App\Core\BaseController;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ScoreController extends BaseController
{

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function score(): void
    {
        $this->render('score');
    }

}