<?php

declare(strict_types=1);

/**
 * Personal Data Manager for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-smartgear
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/personal-data-manager/
 */

namespace WEM\PersonalDataManagerBundle\Controller;

use Contao\BackendTemplate;
use Contao\Controller;
use Contao\Environment;
use Contao\Input;
use Contao\System;
use Contao\User;
use Symfony\Component\Routing\Annotation\Route;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use WEM\PersonalDataManagerBundle\Service\PersonalDataManagerUi;

/**
 * @Route("%contao.backend.route_prefix%/wem-personal-data-manager",
 *     name=BackendController::class,
 *     defaults={"_scope": "backend"}
 * )
 * @ServiceTag("controller.service_arguments")
 */
class PersonalDataManagerController extends Controller
{
    /**
     * Template.
     */
    protected string $strTemplate = 'be_wem_personal_data_manager';

    protected User $user;

    public function __construct(
    ) {
        parent::__construct();
        $GLOBALS['TL_CSS'][] = 'bundles/wempersonaldatamanager/css/pdm.css';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/wempersonaldatamanager/js/pdm-modal.js';
        $GLOBALS['TL_CSS'][] = 'bundles/wempersonaldatamanager/css/pdm-modal.css';
    }

    public function generate(): string
    {
        // Handle ajax request
        if (Input::post('TL_WEM_AJAX')) {
            /** @var PersonalDataManagerUi $pdmAction */
            $pdmAction = System::getContainer()->get('wem.personal_data_manager.service.personal_data_manager_action');
            $pdmAction->processAjaxRequest();
        }

        $tpl = new BackendTemplate($this->strTemplate);

        $tpl->email = Input::post('email') ?? '';
        $tpl->request = Environment::get('request');
        $tpl->token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        if (empty($tpl->email)) {
            $tpl->content = $GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['PleaseFillEmail'];
        } else {
            /** @var PersonalDataManagerUi $pdmUi */
            $pdmUi = System::getContainer()->get('wem.personal_data_manager.service.personal_data_manager_ui');
            $pdmUi->setUrl(System::getContainer()->getParameter('contao.backend.route_prefix').'?do=wem-personal-data-manager');
            $tpl->content = $pdmUi->listForEmail($tpl->email);
        }

        return $tpl->parse();
    }
}
