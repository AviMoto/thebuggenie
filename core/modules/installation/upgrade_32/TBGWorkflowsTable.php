<?php

    namespace thebuggenie\core\modules\installation\upgrade_32;

    /**
     * Workflows table
     *
     * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
     * @version 3.1
     * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
     * @package thebuggenie
     * @subpackage tables
     */

    /**
     * Workflows table
     *
     * @package thebuggenie
     * @subpackage tables
     *
     * @Table(name="workflows")
     */
    class TBGWorkflowsTable extends \TBGB2DBTable
    {

        const B2DBNAME = 'workflows';
        const ID = 'workflows.id';
        const SCOPE = 'workflows.scope';
        const NAME = 'workflows.name';
        const DESCRIPTION = 'workflows.description';
        const IS_ACTIVE = 'workflows.is_active';

        protected function _initialize()
        {
            parent::_setup(self::B2DBNAME, self::ID);
            parent::_addInteger(self::SCOPE, 10);
            parent::_addVarchar(self::NAME, 200);
            parent::_addText(self::DESCRIPTION, false);
            parent::_addBoolean(self::IS_ACTIVE);
        }

    }
