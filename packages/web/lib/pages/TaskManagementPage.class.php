<?php
class TaskManagementPage extends FOGPage {
    public $node = 'task';
    public function __construct($name = '') {
        $this->name = 'Task Management';
        parent::__construct($this->name);
        $this->menu = array(
            'search'=>$this->foglang['NewSearch'],
            'active'=>$this->foglang['ActiveTasks'],
            'listhosts'=>sprintf($this->foglang['ListAll'],$this->foglang['Hosts']),
            'listgroups'=>sprintf($this->foglang['ListAll'],$this->foglang['Groups']),
            'active-multicast'=>$this->foglang['ActiveMCTasks'],
            'active-snapins'=>$this->foglang['ActiveSnapins'],
            'scheduled'=>$this->foglang['ScheduledTasks'],
        );
        $this->subMenu = array();
        $this->notes = array();
        $this->HookManager->processEvent('SUB_MENULINK_DATA',array('menu'=>&$this->menu,'submenu'=>&$this->subMenu,'id'=>&$this->id,'notes'=>&$this->notes));
        $this->headerData = array(
            '',
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction"/>',
            _('Started By:'),
            _('Hostname<br><small>MAC</small>'),
            '',
            _('Start Time'),
            _('Status'),
        );
        $this->templates = array(
            '',
            '<input type="checkbox" name="task[]" value="${id}" class="toggle-action"/>',
            '${startedby}',
            '<p><a href="?node=host&sub=edit&id=${host_id}" title="' . _('Edit Host') . '">${host_name}</a></p><small>${host_mac}</small>',
            '${details_taskname}',
            '<small>${time}</small>',
            '<i class="fa fa-${icon_state} fa-1x icon" title="${state}"></i> <i class="fa fa-${icon_type} fa-1x icon" title="${type}"></i>',
        );
        $this->attributes = array(
            array('width'=>1,'','class'=>'filter-false'),
            array('width'=>16,'class'=>'c filter-false'),
            array('width'=>65,'class'=>'l','id'=>'host-${host_id}'),
            array('width'=>120,'class'=>'l'),
            array('width'=>70,'class'=>'r'),
            array('width'=>100,'class'=>'r'),
            array('width'=>50,'class'=>'r filter-false'),
        );
    }
    public function index() {
        $this->active();
    }
    public function search_post() {
        $ids = $this->getClass('TaskManager')->search();
        foreach ((array)$ids AS $i => &$id) {
            $Task = $this->getClass('Task',$id);
            if (!$Task->isValid()) {
                unset($Task);
                continue;
            }
            $Host = $Task->getHost();
            if (!$Host->isValid()) {
                unset($Task,$Host);
                continue;
            }
            $hostname = $Host->get('name');
            $MAC = $Host->get('mac');
            unset($Host);
            if ($MAC instanceof MACAddress) $MAC = $MAC->__toString();
            else $MAC = $this->getClass('MACAddress',$MAC)->__toString();
            $this->data[] = array(
                'startedby'=>$Task->get('createdBy'),
                'id'=>$Task->get('id'),
                'name'=>$Task->get('name'),
                'time'=>$this->formatTime($Task->get('createdTime')),
                'state'=>$Task->getTaskStateText(),
                'forced'=>($Task->get('isForced') ? 1 : 0),
                'type'=>$Task->getTaskTypeText(),
                'percentText'=>$Task->get('percent'),
                'width'=>600*($Task->get('percent')/100),
                'elapsed'=>$Task->get('timeElapsed'),
                'remains'=>$Task->get('timeRemaining'),
                'percent'=>$Task->get('pct'),
                'copied'=>$Task->get('dataCopied'),
                'total'=>$Task->get('dataTotal'),
                'bpm'=>$Task->get('bpm'),
                'details_taskname'=>($Task->get('name')?sprintf('<div class="task-name">%s</div>',$Task->get('name')):''),
                'details_taskforce'=>($Task->get('isForced')?sprintf('<i class="icon-forced" title="%s"></i>',_('Task forced to start')):($Task->get('typeID') < 3 && $Task->get('stateID') < 3?sprintf('<a href="?node=task&sub=force-task&id=%s" class="icon-force"><i title="%s"></i></a>',$Task->get('id'),_('Force task to start')):'&nbsp;')),
                'host_id'=>$Task->get('hostID'),
                'host_name'=>$hostname,
                'host_mac'=>$MAC,
                'icon_state'=>$Task->getTaskState()->getIcon(),
                'icon_type'=>$Task->getTaskType()->get('icon'),
            );
            unset($Task);
        }
        unset($ids,$id,$hostname,$MAC);
        $this->HookManager->processEvent('HOST_DATA',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
    }
    public function listhosts() {
        $this->title = _('All Hosts');
        $this->headerData = array(
            _('Host Name'),
            _('Image Name'),
            _('Deploy'),
        );
        $this->templates = array(
            '<a href="?node=host&sub=edit&id=${id}"/>${host_name}</a><br /><small>${host_mac}</small>',
            '<small>${image_name}</small>',
            '${downLink}&nbsp;${uploadLink}&nbsp;${advancedLink}',
        );
        $this->attributes = array(
            array('class'=>'l'),
            array('width'=>60,'class'=>'c'),
            array('width'=>60,'class'=>'r filter-false'),
        );
        $ids = $this->getSubObjectIDs('Host','','','','name');
        foreach((array)$ids AS $i => &$id) {
            $Host = $this->getClass('Host',$id);
            if (!$Host->isValid() || $Host->get('pending')) {
                unset($Host);
                continue;
            }
            $hostname = $Host->get('name');
            $MAC = $Host->get('mac');
            $imgUp = '<a href="?node=task&sub=hostdeploy&type=2&id=${id}"><i class="icon hand fa fa-${upicon} fa-1x" title="'._('Upload').'"></i></a>';
            $imgDown = '<a href="?node=task&sub=hostdeploy&type=1&id=${id}"><i class="icon hand fa fa-${downicon} fa-1x" title="'._('Download').'"></i></a>';
            $imgAdvanced = '<a href="?node=task&sub=hostadvanced&id=${id}#host-tasks"><i class="icon hand fa fa-arrows-alt fa-1x" title="'._('Advanced').'"></i></a>';
            $this->data[] = array(
                'uploadLink'=>$imgUp,
                'downLink'=>$imgDown,
                'advancedLink'=>$imgAdvanced,
                'id'=>$Host->get('id'),
                'host_name'=>$Host->get('name'),
                'host_mac'=>$MAC,
                'image_name'=>$Host->getImageName(),
                'upicon'=>$this->getClass('TaskType',2)->get('icon'),
                'downicon'=>$this->getClass('TaskType',1)->get('icon'),
            );
            unset($Host);
        }
        unset($ids,$id);
        $this->HookManager->processEvent('HOST_DATA',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
    }
    public function hostdeploy() {
        $Host = $this->getClass('Host',$_REQUEST['id']);
        $taskTypeID = $_REQUEST['type'];
        $TaskType = $this->getClass('TaskType',$_REQUEST['type']);
        $snapin = '-1';
        $enableShutdown = false;
        $enableSnapins = ($_REQUEST['type'] == 17 ? false : -1);
        $taskName = 'Quick Deploy';
        try {
            if ($TaskType->isUpload() && $Host->getImage()->isValid() && $Host->getImage()->get('protected')) throw new Exception(sprintf('%s: %s %s: %s %s',_('Hostname'),$Host->get('name'),_('Image'),$Host->getImageName(),_('is protected')));
            $Host->createImagePackage($taskTypeID, $taskName, false, false, $enableSnapins, false, $_SESSION['FOG_USERNAME']);
            $this->setMessage(_('Successfully created tasking!'));
            $this->redirect('?node=task&sub=active');
        } catch (Exception $e) {
            printf('<div class="task-start-failed"><p>%s</p><p>%s</p></div>',_('Failed to create deploy task'), $e->getMessage());
        }
    }
    public function hostadvanced() {
        unset($this->headerData);
        $this->attributes =  array(
            array(),
            array(),
        );
        $this->templates = array(
            '<a href="?node=${node}&sub=${sub}&id=${id}&type=${type}"><i class="fa fa-${task_icon} fa-fw fa-2x" /></i><br />${task_name}</a>',
            '${task_desc}',
        );
        echo '<div><h2>'._('Advanced Actions').'</h2>';
        $TaskTypes = $this->getClass('TaskTypeManager')->find(array('access'=>array('both', 'host'),'isAdvanced'=>1),'AND','id');
        foreach ($TaskTypes AS $i => &$TaskType) {
            $this->data[] = array(
                'node' => $_REQUEST['node'],
                'sub' => 'hostdeploy',
                'id' => $_REQUEST['id'],
                'type' => $TaskType->get('id'),
                'task_icon' => $TaskType->get('icon'),
                'task_name' => $TaskType->get('name'),
                'task_desc' => $TaskType->get('description'),
            );
        }
        unset($TaskType);
        $this->HookManager->processEvent('TASK_DATA',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        echo '</div>';
    }
    public function groupadvanced() {
        unset($this->headerData);
        $this->attributes =  array(
            array(),
            array(),
        );
        $this->templates = array(
            '<a href="?node=${node}&sub=${sub}&id=${id}&type=${type}"><i class="fa fa-${task_icon} fa-fw fa-2x" /></i><br />${task_name}</a>',
            '${task_desc}',
        );
        echo '<div><h2>'._('Advanced Actions').'</h2>';
        $TaskTypes = $this->getClass('TaskTypeManager')->find(array('access'=>array('both', 'group'),'isAdvanced'=>1),'AND','id');
        foreach ($TaskTypes AS $i => &$TaskType) {
            $this->data[] = array(
                'node'=>$_REQUEST['node'],
                'sub'=>'groupdeploy',
                'id'=>$_REQUEST['id'],
                'type'=>$TaskType->get('id'),
                'task_icon'=>$TaskType->get('icon'),
                'task_name'=>$TaskType->get('name'),
                'task_desc'=>$TaskType->get('description'),
            );
        }
        unset($TaskTypes);
        $this->HookManager->processEvent('TASK_DATA',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        echo '</div>';
    }
    public function listgroups() {
        $this->title = _('List all Groups');
        $this->headerData = array(
            _('Name'),
            _('Deploy'),
        );
        $this->attributes = array(
            array('class'=>l),
            array('width'=>60,'class'=>'r filter-false'),
        );
        $this->templates = array(
            '<a href="?node=group&sub=edit&id=${id}"/>${name}</a>',
            '${deployLink}&nbsp;${multicastLink}&nbsp;${advancedLink}',
        );
        $ids = $this->getSubObjectIDs('Group');
        foreach ((array)$ids AS $i => &$id) {
            $Group = $this->getClass('Group',$id);
            if (!$Group->isValid()) {
                unset($Group);
                continue;
            }
            $deployLink = '<a href="?node=task&sub=groupdeploy&type=1&id=${id}"><i class="icon hand fa fa-${downicon} fa-1x" title="'._('Download').'"></i></a>';
            $multicastLink = '<a href="?node=task&sub=groupdeploy&type=8&id=${id}"><i class="icon hand fa fa-${multicon} fa-1x" title="'._('Multicast').'"></i></a>';
            $advancedLink = '<a href="?node=task&sub=groupadvanced&id=${id}"><i class="icon hand fa fa-arrows-alt" title="'._('Advanced').'"></i></a>';
            $this->data[] = array(
                'deployLink'=>$deployLink,
                'advancedLink'=>$advancedLink,
                'multicastLink'=>$multicastLink,
                'id'=>$Group->get('id'),
                'name'=>$Group->get('name'),
                'downicon'=>$this->getClass('TaskType',1)->get('icon'),
                'multiicon'=>$this->getClass('TaskType',8)->get('icon'),
            );
        }
        unset($Group);
        $this->HookManager->processEvent('TasksListGroupData',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
    }
    public function groupdeploy() {
        $Group = $this->getClass('Group',$_REQUEST['id']);
        $taskTypeID = $_REQUEST['type'];
        $TaskType = $this->getClass('TaskType',$taskTypeID);
        $snapin = -1;
        $enableShutdown = false;
        $enableSnapins = ($_REQUEST['type'] == 17 ? false : -1);
        $enableDebug = (in_array($_REQUEST['type'],array(3,15,16)) ? true : false);
        $imagingTasks = array(1,2,8,15,16,17,24);
        $taskName = _(($taskTypeID == 8 ? 'Multicast Group Quick Deploy' : 'Group Quick Deploy'));
        try {
            $ids = $Group->get('hosts');
            foreach ((array)$ids AS $i => &$id) {
                $Host = $this->getClass('Host',$id);
                if (!$Host->isValid()) {
                    unset($Host);
                    continue;
                }
                if (in_array($taskTypeID,$imagingTasks) && !$Host->get('imageID')) throw new Exception(_('You need to assign an image to all of the hosts'));
                if (!$Host->checkIfExist($taskTypeID)) throw new Exception(_('To setup download task, you must first upload an image'));
                unset($Host);
            }
            unset($ids,$id);
            $Group->createImagePackage($taskTypeID, $taskName, $enableShutdown, $enableDebug, $enableSnapins, true, $_SESSION['FOG_USERNAME']);
            $this->setMessage('Successfully created Group tasking!');
            $this->redirect('?node=task&sub=active');
        } catch (Exception $e) {
            $this->setMessage($e->getMessage());
            $this->redirect('?node=task&sub=listgroups');
        }
    }
    // Active Tasks
    public function active() {
        unset($this->data);
        $this->form = '<center><input type="button" id="taskpause"/></center><br/>';
        $this->title = _('Active Tasks');
        $i = 0;
        $ids = $this->getSubObjectIDs('Task',array('stateID'=>array(1,2,3)));
        foreach ($ids AS $i => &$id) {
            $Task = $this->getClass('Task',$id);
            if (!$Task->isValid()) {
                unset($Task);
                continue;
            }
            $Host = $Task->getHost();
            if (!$Host->isValid()) {
                unset($Task,$Host);
                continue;
            }
            $hostname = $Host->get('name');
            $MAC = $Host->get('mac');
            if ($MAC instanceof MACAddress) $MAC = $MAC->__toString();
            else $MAC = $this->getClass('MACAddress',$MAC)->__toString();
            $this->data[] = array(
                'startedby'=>$Task->get('createdBy'),
                'id'=>$Task->get('id'),
                'name'=>$Task->get('name'),
                'time'=>$this->formatTime($Task->get('createdTime')),
                'state'=>$Task->getTaskStateText(),
                'forced'=>$Task->get('isForced'),
                'type'=>$Task->getTaskTypeText(),
                'percentText'=>$Task->get('percent'),
                'width'=> 600 * ($Task->get('percent')/100),
                'elapsed'=>$Task->get('timeElapsed'),
                'remains'=>$Task->get('timeRemaining'),
                'percent'=>$Task->get('pct'),
                'copied'=>$Task->get('dataCopied'),
                'total'=>$Task->get('dataTotal'),
                'bpm'=>$Task->get('bpm'),
                'details_taskname'=>($Task->get('name')?sprintf('<div class="task-name">%s</div>',$Task->get('name')):''),
                'details_taskforce'=>($Task->get('isForced') ? sprintf('<i class="icon-forced" title="%s"></i>', _('Task forced to start')) : ($Task->get('typeID') < 3 && $Task->get('stateID') < 3 ? sprintf('<a href="?node=task&sub=force-task&id=%s" class="icon-force"><i title="%s"></i></a>', $Task->get('id'),_('Force task to start')) : '&nbsp;')),
                'host_id'=>$Host->get('id'),
                'host_name'=>$hostname,
                'host_mac'=>$MAC,
                'icon_state'=>$Task->getTaskState()->getIcon(),
                'icon_type'=>$Task->getTaskType()->get('icon'),
            );
        }
        unset($Task);
        $this->HookManager->processEvent('HOST_DATA',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
    }
    public function canceltasks() {
        $ids = $this->getSubObjectIDs('Task',array('id'=>(array)$_REQUEST['task']));
        foreach ((array)$ids AS $i => &$id) {
            $Task = $this->getClass('Task',$id);
            if (!$Task->isValid()) {
                unset($Task);
                continue;
            }
            $Task->cancel();
            unset($Task);
        }
    }
    public function force_task() {
        $Task = $this->getClass('Task',$_REQUEST['id']);
        $this->HookManager->processEvent('TASK_FORCE',array('Task'=>&$Task));
        unset($result);
        try {
            if ($Task->set('isForced',1)->save()) $result['success'] = true;
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        if ($this->ajax) echo json_encode($result);
        else {
            if ($result['error']) $this->fatalError($result['error']);
            else $this->redirect(sprintf('?node=%s',$this->node));
        }
    }
    public function cancel_task() {
        $Task = $this->getClass('Task',$_REQUEST['id']);
        $this->HookManager->processEvent('TASK_CANCEL',array('Task'=>&$Task));
        try {
            $Task->cancel();
            $result['success'] = true;
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        if ($this->isAJAXRequest()) echo json_encode($result);
        else {
            if ($result['error']) $this->fatalError($result['error']);
            else $this->redirect(sprintf('?node=%s', $this->node));
        }
    }
    public function remove_multicast_post() {
        $MulticastSessionIDs = $this->getSubObjectIDs('MulticastSessions',array('id'=>$_REQUEST['task']));
        $TaskIDs = $this->getSubObjectIDs('MulticastSessionsAssociation',array('id'=>$_REQUEST['task']),'taskID');
        foreach ($TaskIDs AS $i => &$id) {
            $Task = $this->getClass('Task',$id);
            if (!$Task->isValid()) {
                unset($Task);
                continue;
            }
            $Task->cancel();
            unset($Task);
        }
        $this->getClass('MulticastSessionsAssociationManager')->destroy(array('taskID'=>$TaskIDs));
        $this->getClass('MulticastSessionsManager')->destroy(array('id'=>$MulticastSessionIDs));
        unset($TaskIDs,$id);
        $this->setMessage(_('Successfully cancelled selected tasks'));
        $this->redirect('?node='.$this->node.'&sub=active');
    }
    public function active_multicast() {
        $this->title = _('Active Multi-cast Tasks');
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction"/>',
            _('Task Name'),
            _('Hosts'),
            _('Start Time'),
            _('State'),
            _('Status'),
        );
        $this->templates = array(
            '<input type="checkbox" name="task[]" value="${id}" class="toggle-action"/>',
            '${name}',
            '${count}',
            '${start_date}',
            '${state}',
            '${percent}',
        );
        $this->attributes = array(
            array('width'=>16,'class'=>'c'),
            array('class'=>'c'),
            array('class'=>'c'),
            array('class'=>'c'),
            array('class'=>'c'),
            array('width'=>40,'class'=>'c')
        );
        $ids = $this->getSubObjectIDs('MulticastSessions',array('stateID'=>array(1,2,3)));
        foreach($ids AS $i => &$id) {
            $MS = $this->getClass('MulticastSessions',$id);
            if (!$MS->isValid()) {
                unset($MS);
                continue;
            }
            $TS = $this->getClass('TaskState',$MS->get('stateID'));
            $this->data[] = array(
                'id'=>$MS->get('id'),
                'name'=>($MS->get('name')?$MS->get('name'): _('Multicast Task')),
                'count'=>$this->getClass('MulticastSessionsAssociationManager')->count(array('msID'=>$MS->get('id'))),
                'start_date'=>$this->formatTime($MS->get('starttime')),
                'state'=>($TS->get('name')?$TS->get('name'):null),
                'percent'=>$MS->get('percent'),
            );
            unset($MS,$TS);
        }
        unset($id);
        $this->HookManager->processEvent('TaskActiveMulticastData',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
    }
    public function active_snapins() {
        $this->title = 'Active Snapins';
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction"/>',
            _('Host Name'),
            _('Snapin'),
            _('Start Time'),
            _('State'),
        );
        $this->templates = array(
            '<input type="checkbox" name="task[]" value="${id}" class="toggle-action"/>',
            '${host_name}',
            '<form method="post" method="?node=task&sub=active-snapins">${name}',
            '${startDate}',
            '${state}',
        );
        $this->attributes = array(
            array('class'=>'c filter-false','width'=>16),
            array('class'=>'l','width'=>50),
            array('class'=>'l','width'=>50),
            array('class'=>'l','width'=>50),
            array('class'=>'r','width'=>40),
        );
        $TaskIDs = $this->getSubObjectIDs('SnapinTask',array('stateID'=>array(-1,0,1,2,3)));
        foreach($TaskIDs AS $i => &$id) {
            $SnapinTask = $this->getClass('SnapinTask',$id);
            if (!$SnapinTask->isValid()) {
                unset($SnapinTask);
                continue;
            }
            $Host = $this->getClass('SnapinJob',$SnapinTask->get('jobID'))->getHost();
            if (!$Host->isValid()) {
                unset($Host,$SnapinTask);
                continue;
            }
            if (!$Host->get('snapinjob')->isValid()) {
                unset($Host,$SnapinTask);
                continue;
            }
            $Snapin = $this->getClass('Snapin',$SnapinTask->get('snapinID'));
            if (!$Snapin->isValid()) {
                unset($Host,$SnapinTask,$Snapin);
                continue;
            }
            if (in_array($Host->get('snapinjob')->get('stateID'),array(-1,0,1,2,3))) {
                $this->data[] = array(
                    'id' => $SnapinTask->get('id'),
                    'name' => $Snapin->get('name'),
                    'hostID' => $Host->get('id'),
                    'host_name' => $Host->get('name'),
                    'startDate' => $this->formatTime($SnapinTask->get('checkin')),
                    'state' => $this->getClass('TaskState',$SnapinTask->get('stateID'))->get('name'),
                );
            }
            unset($Host,$SnapinTask,$Snapin);
        }
        unset($ids,$id);
        $this->HookManager->processEvent('TaskActiveSnapinsData',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
    }
    public function active_snapins_post() {
        $SnapinTaskIDs = $this->getSubObjectIDs('SnapinTask',array('id'=>$_REQUEST['task']));
        $SnapinJobIDs = $this->getSubObjectIDs('SnapinTask',array('id'=>$_REQUEST['task']),'jobID');
        $this->getClass('SnapinTaskManager')->destroy(array('id'=>$SnapinTaskIDs,'jobID'=>$SnapinJobIDs));
        $this->getClass('SnapinJobManager')->destroy(array('id'=>$SnapinJobIDs));
        $this->setMessage(_('Successfully cancelled selected tasks'));
        $this->redirect('?node='.$this->node.'&sub=active');
    }
    public function cancelscheduled() {
        $this->getClass('ScheduledTaskManager')->destroy(array('id'=>$_REQUEST['task']));
        $this->setMessage(_('Successfully cancelled selected tasks'));
        $this->redirect('?node='.$this->node.'&sub=active');
    }
    public function scheduled() {
        $this->title = 'Scheduled Tasks';
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction"/>',
            _('Name:'),
            _('Is Group'),
            _('Task Name'),
            _('Task Type'),
            _('Start Time'),
            _('Active/Type'),
        );
        $this->templates = array(
            '<input type="checkbox" name="task[]" value="${id}" class="toggle-action"/>',
            '<a href="?node=${hostgroup}&sub=edit&id=${host_id}" title="Edit ${hostgroupname}">${hostgroupname}</a>',
            '${groupbased}<form method="post" action="?node=task&sub=scheduled">',
            '${details_taskname}',
            '${task_type}',
            '<small>${time}</small>',
            '${active}/${type}',
        );
        $this->attributes = array(
            array('width'=>16,'class'=>'c'),
            array('width'=>120,'class'=>'l'),
            array(),
            array('width'=>110,'class'=>'l'),
            array('class'=>'c','width'=>80),
            array('width'=>70,'class'=>'c'),
            array('width'=>100,'class'=>'c','style'=>'padding-right: 10px'),
        );
        $ids = $this->getSubObjectIDs('ScheduledTask');
        foreach ((array)$ids AS $i => &$id) {
            $task = $this->getClass('ScheduledTask',$id);
            if (!$task->isValid()) {
                unset($task);
                continue;
            }
            $Host = $task->getHost();
            if (!$Host->isValid()) {
                unset($task,$Host);
                continue;
            }
            $taskType = $task->getTaskType();
            if ($task->get('type') == 'C') $taskTime = FOGCron::parse($this->FOGCore,$task->get('minute').' '.$task->get('hour').' '.$task->get('dayOfMonth').' '.$task->get('month').' '.$task->get('dayOfWeek'));
            else $taskTime = $task->get('scheduleTime');
            $taskTime = $this->nice_date()->setTimestamp($taskTime);
            $hostGroupName = ($task->isGroupBased() ? $task->getGroup() : $task->getHost());
            $this->data[] = array(
                'id'=>$task->get('id'),
                'hostgroup'=>$task->isGroupBased() ? 'group' : 'host',
                'hostgroupname'=>$hostGroupName,
                'host_id'=>$hostGroupName->get('id'),
                'groupbased'=>$task->isGroupBased() ? _('Yes') : _('No'),
                'details_taskname'=>$task->get('name'),
                'time'=>$this->formatTime($taskTime),
                'active'=>$task->get('isActive') ? 'Yes' : 'No',
                'type'=>$task->get('type') == 'C' ? 'Cron' : 'Delayed',
                'schedtaskid'=>$task->get('id'),
                'task_type'=>$taskType,
            );
            unset($task,$Host,$taskType,$taskTime,$hostGroupName);
        }
        unset($id);
        $this->HookManager->processEvent('TaskScheduledData',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
    }
}
