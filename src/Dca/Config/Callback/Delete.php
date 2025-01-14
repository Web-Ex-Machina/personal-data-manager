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

namespace WEM\PersonalDataManagerBundle\Dca\Config\Callback;

use Contao\DataContainer;
use Contao\Model;
use Exception;
use WEM\PersonalDataManagerBundle\Service\PersonalDataManager;

class Delete
{
    protected PersonalDataManager $personalDataManager;

    public function __construct(
        PersonalDataManager $personalDataManager
    ) {
        $this->personalDataManager = $personalDataManager;
    }

    /**
     * @throws Exception
     */
    public function __invoke(DataContainer $dc, int $undoId): void
    {
        if (!$dc->id) {
            return;
        }

        $modelClassName = Model::getClassFromTable($dc->table);
        $model = new $modelClassName();

        $this->personalDataManager->deleteByPidAndPtableAndEmail(
            (int) $dc->activeRecord->{$model->getPersonalDataPidField()},
            $model->getPersonalDataPtable(),
            $dc->activeRecord->{$model->getPersonalDataEmailField()}
        );
    }
}
