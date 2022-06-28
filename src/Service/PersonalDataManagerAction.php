<?php

declare(strict_types=1);

/**
 * Personal Data Manager for Contao Open Source CMS
 * Copyright (c) 2015-2022 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-smartgear
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/personal-data-manager/
 */

namespace WEM\PersonalDataManagerBundle\Service;

use Contao\Input;
use Contao\Request;
use Contao\RequestToken;
use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use WEM\PersonalDataManagerBundle\Exception\AccessDeniedException;

class PersonalDataManagerAction
{
    /** @var TranslatorInterface */
    private $translator;
    /** @var PersonalDataManager */
    private $manager;

    public function __construct(
        TranslatorInterface $translator,
        PersonalDataManager $manager
    ) {
        $this->translator = $translator;
        $this->manager = $manager;
    }

    /**
     * Process AJAX actions.
     *
     * @return string - Ajax response, as String or JSON
     */
    public function processAjaxRequest()
    {
        // Catch AJAX Requests
        if (Input::post('TL_WEM_AJAX') && 'be_pdm' === Input::post('wem_module')) {
            try {
                switch (Input::post('action')) {
                    case 'anonymize_single_personal_data':
                        $arrResponse = $this->anonymizeSinglePersonalData();
                    break;
                    case 'anonymize_personal_data_item':
                        $arrResponse = $this->anonymizeSingleItem();
                    break;
                    case 'anonymize_all_personal_data':
                        $arrResponse = $this->anonymizeAllPersonalData();
                    break;
                    case 'export_single':
                        $this->exportSingleItem();
                    break;
                    case 'export_all':
                        $this->exportAllPersonalData();
                    break;
                    case 'show_personal_data_item':
                        $arrResponse = $this->showSingleItem();
                    break;
                    default:
                        throw new Exception('Unknown route');
                }
            } catch (AccessDeniedException $e) {
                $arrResponse = ['status' => 'error', 'msg' => $e->getMessage(), 'trace' => $e->getTrace()];
            } catch (Exception $e) {
                $arrResponse = ['status' => 'error', 'msg' => $e->getMessage(), 'trace' => $e->getTrace()];
            }

            // Add Request Token to JSON answer and return
            $arrResponse['rt'] = RequestToken::get();
            $response = new Response(json_encode($arrResponse), 'error' === $arrResponse['status'] ? 401 : 200);
            $response->send();

            exit;
        }
    }

    protected function anonymizeSinglePersonalData(): array
    {
        if (empty(Input::post('pid'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['PleaseFillEmail']);
        }

        if (empty(Input::post('ptable'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['ptableEmpty']);
        }

        if (empty(Input::post('email'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['emailEmpty']);
        }

        if (empty(Input::post('field'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['fieldEmpty']);
        }
        $this->checkAccess();

        $anonymizeValue = $this->manager->anonymizeByPidAndPtableAndEmailAndField(Input::post('pid'), Input::post('ptable'), Input::post('email'), Input::post('field'));

        return [
            'status' => 'success',
            'msg' => '',
            'value' => $anonymizeValue,
        ];
    }

    protected function anonymizeSingleItem(): array
    {
        if (empty(Input::post('pid'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['PleaseFillEmail']);
        }

        if (empty(Input::post('ptable'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['ptableEmpty']);
        }

        if (empty(Input::post('email'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['emailEmpty']);
        }

        $this->checkAccess();

        $anonymizeValues = $this->manager->anonymizeByPidAndPtableAndEmail(Input::post('pid'), Input::post('ptable'), Input::post('email'));

        return [
            'status' => 'success',
            'msg' => '',
            'values' => $anonymizeValues,
        ];
    }

    protected function anonymizeAllPersonalData(): array
    {
        if (empty(Input::post('email'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['emailEmpty']);
        }

        $this->checkAccess();

        $anonymizeValues = $this->manager->anonymizeByEmail(Input::post('email'));

        return [
            'status' => 'success',
            'msg' => '',
            'values' => $anonymizeValues,
        ];
    }

    protected function exportSingleItem(): void
    {
        if (empty(Input::post('pid'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['PleaseFillEmail']);
        }

        if (empty(Input::post('ptable'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['ptableEmpty']);
        }

        if (empty(Input::post('email'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['emailEmpty']);
        }

        $this->checkAccess();

        $csv = $this->manager->exportByPidAndPtableAndEmail(Input::post('pid'), Input::post('ptable'), Input::post('email'));

        (new Response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment', 'filename' => 'filename.csv']))->send();
        exit();
    }

    protected function exportAllPersonalData(): void
    {
        if (empty(Input::post('email'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['emailEmpty']);
        }

        $this->checkAccess();

        $csv = $this->manager->exportByEmail(Input::post('email'));

        (new Response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment', 'filename' => 'filename.csv']))->send();
        exit();
    }

    protected function showSingleItem(): array
    {
        if (empty(Input::post('pid'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['pidEmpty']);
        }

        if (empty(Input::post('ptable'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['ptableEmpty']);
        }

        if (empty(Input::post('email'))) {
            throw new InvalidArgumentException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['emailEmpty']);
        }

        $this->checkAccess();

        $href = $this->manager->getHrefByPidAndPtableAndEmail(Input::post('pid'), Input::post('ptable'), Input::post('email'));

        return [
            'status' => empty($href) ? 'error' : 'success',
            'msg' => empty($href) ? $GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['noUrlProvided'] : '',
            'href' => $href,
        ];
    }

    protected function checkAccess(): void
    {
        $this->user = \Contao\BackendUser::getInstance();

        if ($this->user->isAdmin) {
            return;
        }

        $this->user = \Contao\FrontendUser::getInstance();
        // if (!$this->user->id) {
        //     throw new AccessDeniedException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['accessDenied']);
        // }
        if (!$this->manager->isEmailTokenCoupleValid(Input::post('email'), $this->manager->getTokenInSession())) {
            $this->manager->clearTokenInSession();
            throw new AccessDeniedException($GLOBALS['TL_LANG']['WEM']['PEDAMA']['DEFAULT']['accessDenied']);
        }
        $this->manager->updateTokenExpiration($this->manager->getTokenInSession());
    }
}
