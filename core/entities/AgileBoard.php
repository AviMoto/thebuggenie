<?php

    namespace thebuggenie\core\entities;

    /**
     * Agile board class
     *
     * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
     * @version 3.1
     * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
     * @package thebuggenie
     * @subpackage main
     */

    /**
     * Agile board class
     *
     * @package thebuggenie
     * @subpackage main
     *
     * @Table(name="\thebuggenie\core\entities\b2db\AgileBoards")
     */
    class AgileBoard extends \TBGIdentifiableScopedClass
    {

        const TYPE_GENERIC = 0;
        const TYPE_SCRUM = 1;
        const TYPE_KANBAN = 2;

        const SWIMLANES_ISSUES = 'issues';
        const SWIMLANES_GROUPING = 'grouping';
        const SWIMLANES_EXPEDITE = 'expedite';

        /**
         * The name of the board
         *
         * @var string
         * @Column(type="string", length=200)
         */
        protected $_name;

        /**
         * Board description
         *
         * @var string
         * @Column(type="string", length=200)
         */
        protected $_description;

        /**
         * Whether this board is the private
         *
         * @var boolean
         * @Column(type="boolean", default=1)
         */
        protected $_is_private = true;

        /**
         * @var \TBGUser
         * @Column(type="integer", length=10)
         * @Relates(class="\TBGUser")
         */
        protected $_user_id;

        /**
         * @var \TBGProject
         * @Column(type="integer", length=10)
         * @Relates(class="\TBGProject")
         */
        protected $_project_id;

        /**
         * @var \TBGIssuetype
         * @Column(type="integer", length=10)
         * @Relates(class="\TBGIssuetype")
         */
        protected $_epic_issuetype_id;

        /**
         * @var \TBGIssuetype
         * @Column(type="integer", length=10)
         * @Relates(class="\TBGIssuetype")
         */
        protected $_task_issuetype_id;

        /**
         * @var \TBGSavedSearch
         * @Column(type="integer", length=10)
         * @Relates(class="\TBGSavedSearch")
         */
        protected $_backlog_search_id;

        /**
         * @var integer
         * @Column(type="integer", length=10)
         */
        protected $_autogenerated_search;

        /**
         * The board type
         *
         * @var integer
         * @Column(type="integer", length=10)
         */
        protected $_type = self::TYPE_SCRUM;

        /**
         * Whether to use swimlanes
         *
         * @var boolean
         * @Column(type="boolean", default=false)
         */
        protected $_use_swimlanes = false;

        protected $_swimlanes = array();

        /**
         * Swimlane type
         *
         * @var string
         * @Column(type="string", length=50, default="issuetype")
         */
        protected $_swimlane_type = self::SWIMLANES_ISSUES;

        /**
         * Swimlane identifier field
         *
         * @var string
         * @Column(type="string", length=50, default="issuetype")
         */
        protected $_swimlane_identifier = "issuetype";

        /**
         * Swimlane field value
         *
         * @var array
         * @Column(type="serializable", length=500)
         */
        protected $_swimlane_field_values = array();

        /**
         * Cached search object
         * @var \TBGSavedSearch
         */
        protected $_search_object;

        /**
         * Array of epic issues
         *
         * @var array|\TBGIssue
         */
        protected $_epic_issues = null;

        /**
         * Board columns
         *
         * @var array|\thebuggenie\core\entities\BoardColumn
         * @Relates(class="\thebuggenie\core\entities\BoardColumn", collection=true, foreign_column="board_id", orderby="sort_order")
         */
        protected $_board_columns = null;

        /**
         * Returns the associated user
         *
         * @return \TBGUser
         */
        public function getUser()
        {
            return $this->_b2dbLazyload('_user_id');
        }

        public function setUser($user)
        {
            $this->_user_id = $user;
        }

        /**
         * Returns the associated project
         *
         * @return \TBGProject
         */
        public function getProject()
        {
            return $this->_b2dbLazyload('_project_id');
        }

        public function setProject($project)
        {
            $this->_project_id = $project;
        }

        /**
         * Returns the associated epic issue type
         *
         * @return \TBGIssuetype
         */
        public function getEpicIssuetype()
        {
            return $this->_b2dbLazyload('_epic_issuetype_id');
        }

        public function getEpicIssuetypeID()
        {
            return ($this->getEpicIssuetype() instanceof \TBGIssuetype) ? $this->getEpicIssuetype()->getID() : 0;
        }

        public function setEpicIssuetype($epic_issuetype_id)
        {
            $this->_epic_issuetype_id = $epic_issuetype_id;
        }

        /**
         * Returns the associated task issue type
         *
         * @return \TBGIssuetype
         */
        public function getTaskIssuetype()
        {
            return $this->_b2dbLazyload('_task_issuetype_id');
        }

        public function setTaskIssuetype($task_issuetype_id)
        {
            $this->_task_issuetype_id = $task_issuetype_id;
        }

        public function getTaskIssuetypeID()
        {
            return ($this->getTaskIssuetype() instanceof \TBGIssuetype) ? $this->getTaskIssuetype()->getID() : 0;
        }

        /**
         * Returns the associated backlog saved search
         *
         * @return \TBGSavedSearch
         */
        public function getBacklogSearch()
        {
            return $this->_b2dbLazyload('_backlog_search_id');
        }

        public function setBacklogSearch($backlog_search)
        {
            $this->_backlog_search_id = $backlog_search;
            $this->_autogenerated_search = null;
            $this->_search_object = null;
        }

        public function setAutogeneratedSearch($autogenerated_search)
        {
            $this->_autogenerated_search = $autogenerated_search;
            $this->_backlog_search_id = null;
            $this->_search_object = null;
        }

        public function getAutogeneratedSearch()
        {
            return $this->_autogenerated_search;
        }

        public function usesAutogeneratedSearchBacklog()
        {
            return (bool) $this->_autogenerated_search;
        }

        public function usesSavedSearchBacklog()
        {
            return (bool) $this->_backlog_search_id;
        }

        /**
         * Returns the associated search object
         *
         * @return \TBGSavedSearch
         */
        public function getBacklogSearchObject()
        {
            if ($this->_search_object === null)
            {
                if ($this->usesSavedSearchBacklog())
                {
                    $this->_search_object = $this->getBacklogSearch();
                }
                elseif (!$this->_search_object instanceof \TBGSavedSearch)
                {
                    $this->_search_object = \TBGSavedSearch::getPredefinedSearchObject($this->_autogenerated_search);
                }
                $this->_search_object->setIssuesPerPage(0);
                $this->_search_object->setOffset(0);
                $this->_search_object->setFilter('issuetype', \TBGSearchFilter::createFilter('issuetype', array('o' => '!=', 'v' => $this->getEpicIssuetypeID())));
                $this->_search_object->setFilter('milestone', \TBGSearchFilter::createFilter('milestone', array('o' => '!=', 'v' => null)));
                $this->_search_object->setSortFields(array('issues.milestone_order' => 'desc'));
            }

            return $this->_search_object;
        }

        public function getBacklogSearchIdentifier()
        {
            return ($this->usesAutogeneratedSearchBacklog()) ? 'predefined_' . $this->getAutogeneratedSearch() : 'saved_' . $this->getBacklogSearchObject()->getID();
        }

        public function getName()
        {
            return $this->_name;
        }

        public function setName($name)
        {
            $this->_name = $name;
        }

        public function getDescription()
        {
            return $this->_description;
        }

        public function hasDescription()
        {
            return (bool) ($this->getDescription() != '');
        }

        public function setDescription($description)
        {
            $this->_description = $description;
        }

        public function getIsPrivate()
        {
            return $this->_is_private;
        }

        public function isPrivate()
        {
            return $this->getIsPrivate();
        }

        public function setIsPrivate($is_private)
        {
            $this->_is_private = $is_private;
        }

        public function getType()
        {
            return $this->_type;
        }

        public function setType($type)
        {
            $this->_type = $type;
        }

        public function getBacklogIssuesUrl()
        {
            if ($this->usesSavedSearchBacklog())
            {
                $url = \TBGContext::getRouting()->generate('project_issues', array('project_key' => $this->getProject()->getKey(), 'saved_search' => $this->getBacklogSearch()->getID(), 'search' => true, 'format' => 'backlog'));
            }
            else
            {
                $url = \TBGContext::getRouting()->generate('project_issues', array('project_key' => $this->getProject()->getKey(), 'predefined_search' => $this->getAutogeneratedSearch(), 'search' => true, 'format' => 'backlog'));
            }

            return $url;
        }

        public function getEpicIssues()
        {
            if ($this->_epic_issues === null)
            {
                $this->_epic_issues = \TBGIssuesTable::getTable()->getOpenIssuesByProjectIDAndIssuetypeID($this->getProject()->getID(), $this->getEpicIssuetypeID());
            }
            return $this->_epic_issues;
        }

        public function getMilestones()
        {
            return $this->getProject()->getOpenMilestones();
        }

        public function getReleases()
        {
            return $this->getProject()->getUnreleasedBuilds();
        }

        public function usesSwimlanes()
        {
            return $this->_use_swimlanes;
        }

        public function getSwimlaneType()
        {
            return $this->_swimlane_type;
        }

        public function getSwimlaneIdentifier()
        {
            return $this->_swimlane_identifier;
        }

        public function getSwimlaneFieldValues()
        {
            return $this->_swimlane_field_values;
        }

        public function setUseSwimlanes($use_swimlanes = true)
        {
            $this->_use_swimlanes = $use_swimlanes;
        }

        public function useSwimlanes($use_swimlanes = true)
        {
            $this->setUseSwimlanes($use_swimlanes);
        }

        public function setSwimlaneType($swimlane_type)
        {
            $this->_swimlane_type = $swimlane_type;
        }

        public function clearSwimlaneType()
        {
            $this->_swimlane_type = null;
        }

        public function setSwimlaneIdentifier($swimlane_identifier)
        {
            $this->_swimlane_identifier = $swimlane_identifier;
        }

        public function clearSwimlaneIdentifier()
        {
            $this->_swimlane_identifier = null;
        }

        public function setSwimlaneFieldValues($swimlane_field_values)
        {
            $this->_swimlane_field_values = $swimlane_field_values;
        }

        public function clearSwimlaneFieldValues()
        {
            $this->_swimlane_field_values = array();
        }

        public function hasSwimlaneFieldValue($value)
        {
            return in_array($value, $this->getSwimlaneFieldValues());
        }

        public function hasSwimlaneFieldValues()
        {
            return (count($this->getSwimlaneFieldValues()) > 0);
        }

        /**
         * Returns an array of board columns
         *
         * @return array|\thebuggenie\core\entities\BoardColumn
         */
        public function getColumns()
        {
            return $this->_b2dbLazyload('_board_columns');
        }

        protected function _populateMilestoneSwimlanes(\TBGMilestone $milestone)
        {
            if (!array_key_exists($milestone->getID(), $this->_swimlanes))
            {
                $this->_swimlanes[$milestone->getID()] = array();
                $swimlanes = array();
                if ($this->usesSwimlanes())
                {
                    switch ($this->getSwimlaneType())
                    {
                        case self::SWIMLANES_EXPEDITE:
                        case self::SWIMLANES_GROUPING:
                            switch ($this->getSwimlaneIdentifier())
                            {
                                case 'priority':
                                    $items = \TBGPriority::getAll();
                                    break;
                                case 'severity':
                                    $items = \TBGSeverity::getAll();
                                    break;
                                case 'category':
                                    $items = \TBGCategory::getAll();
                                    break;
                            }
                            if ($this->getSwimlaneType() == self::SWIMLANES_EXPEDITE)
                            {
                                $expedite_items = array();
                                foreach ($this->getSwimlaneFieldValues() as $value)
                                {
                                    if (array_key_exists($value, $items))
                                    {
                                        $expedite_items[$items[$value]->getID()] = $items[$value];
                                        unset($items[$value]);
                                    }
                                }

                                $swimlanes[] = array('identifiables' => $expedite_items);
                                $swimlanes[] = array('identifiables' => $items);
                                $swimlanes[] = array('identifiables' => 0);
                            }
                            else
                            {
                                foreach ($items as $item)
                                {
                                    $swimlanes[] = array('identifiables' => $item);
                                }
                                $swimlanes[] = array('identifiables' => 0);
                            }
                            break;
                        case self::SWIMLANES_ISSUES:
                            foreach ($milestone->getIssues() as $issue)
                            {
                                if (in_array($issue->getIssueType()->getID(), $this->getSwimlaneFieldValues()))
                                {
                                    $swimlanes[] = array('identifiables' => $issue);
                                }
                            }
                            $swimlanes[] = array('identifiables' => 0);
                            break;
                    }
                }
                else
                {
                    $swimlanes[] = array('identifiables' => 0);
                }

                foreach ($swimlanes as $details)
                {
                    $swimlane = new BoardSwimlane();
                    $swimlane->setBoard($this);
                    $swimlane->setIdentifiables($details['identifiables']);
                    $swimlane->setMilestone($milestone);
                    $this->_swimlanes[$milestone->getID()][] = $swimlane;
                }
            }
        }

        /**
         * Retrieve all available swimlanes for the selected milestone
         *
         * @param \TBGMilestone $milestone
         * @return array|BoardSwimlane
         */
        public function getMilestoneSwimlanes($milestone)
        {
            $this->_populateMilestoneSwimlanes($milestone);

            return $this->_swimlanes[$milestone->getID()];
        }

    }
