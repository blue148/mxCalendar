<?php


class mxCalendars {
    
    public $modx;
    public $config = array();
    public $tz;
    
    function __construct(modX &$modx,array $config = array()) {
        $this->modx =& $modx;
        
        $basePath = $this->modx->getOption('mxcalendars.core_path',$config,$this->modx->getOption('core_path').'components/mxcalendars/');
        $assetsUrl = $this->modx->getOption('mxcalendars.assets_url',$config,$this->modx->getOption('assets_url').'components/mxcalendars/');
        $this->config = array_merge(array(
            'basePath' => $basePath,
            'corePath' => $basePath,
            'modelPath' => $basePath.'model/',
            'processorsPath' => $basePath.'processors/',
            'chunksPath' => $basePath.'elements/chunks/',
            'jsUrl' => $assetsUrl.'js/',
            'cssUrl' => $assetsUrl.'css/',
            'assetsUrl' => $assetsUrl,
            'connectorUrl' => $assetsUrl.'connector.php',
        ),$config);
        $this->modx->addPackage('mxcalendars',$this->config['modelPath']);
        $this->modx->getService('lexicon','modLexicon');
        $this->modx->lexicon->load('mxcalendars:default');
    }
    
	/*
	 * MANAGER: Initialize the manager view for calendar item management
	 */
	public function initialize($ctx = 'web') {
	   switch ($ctx) {
			case 'mgr':
				$this->modx->lexicon->load('mxcalendars:default');
				if (!$this->modx->loadClass('mxcalendarControllerRequest',$this->config['modelPath'].'mxcalendars/request/',true,true)) {
				   return 'Could not load controller request handler. ['.$this->config['modelPath'].'mxcalendars/request/]';
				}
				$this->request = new mxcalendarControllerRequest($this);
				return $this->request->handleRequest();
			break;
		}
		return true;
	}
    
	/*
	 * GLOBAL HELPER FUNCTIONS: do what we can to making life easier
	 */ 
        public function loadChunk($name) {
		$chunk = null;
		if (!isset($this->chunks[$name])) {
			$chunk = $this->_getTplChunk($name);
			if (empty($chunk)) {
				$chunk = $this->modx->getObject('modChunk',array('name' => $name));
				if ($chunk == false) return false;
			}
			$this->chunks[$name] = $chunk->getContent();
		} else {
			$o = $this->chunks[$name];
			$chunk = $this->modx->newObject('modChunk');
			//$chunk->set('name', $name);
                        $chunk->setContent($o);
                        //$chunk->save();
		}
		$chunk->setCacheable(false);
                return $chunk;
	}
        //@TODO remove; not used
        public function parseChunk($name,$properties=array()){
		return $this->modx->getChunk($name,$properties);
        }
        
        public function getChunk($name,$properties = array()) {
		$chunk = null;
		if (!isset($this->chunks[$name])) {
			$chunk = $this->_getTplChunk($name);
			if (empty($chunk)) {
				$chunk = $this->modx->getObject('modChunk',array('name' => $name));
				if ($chunk == false) return false;
			}
			$this->chunks[$name] = $chunk->getContent();
		} else {
			$o = $this->chunks[$name];
			$chunk = $this->modx->newObject('modChunk');
			$chunk->setContent($o);
		}
		$chunk->setCacheable(false);
		return $chunk->process($properties);
	}
	 
	private function _getTplChunk($name,$postfix = '.chunk.tpl') {
		$chunk = false;
		$f = $this->config['chunksPath'].strtolower($name).$postfix;
		if (file_exists($f)) {
			$o = file_get_contents($f);
			$chunk = $this->modx->newObject('modChunk');
			$chunk->set('name',$name);
			$chunk->setContent($o);
		}
		return $chunk;
	}
        
        /*
         * SNIPPET FUNCTIONS
         */
        public function setTimeZone(){
            if(date_default_timezone_get() != 'UTC') {
                $this->tz = date_default_timezone_get();
                date_default_timezone_set('UTC');
            }
        }
        public function restoreTimeZone(){
            if(!empty($this->tz)) date_default_timezone_set($this->tz);
        }
        //-- Custom function to get somewhat valid duration; it's fuzzy and can be updated to be more accurate
        public function datediff($datefrom, $dateto, $using_timestamps = false)
        {
                /*
                 * Returns an array with:years, months,days,hours,minutes
                 */
                if (!$using_timestamps) {
                        $datefrom = strtotime($datefrom, 0);
                        $dateto = strtotime($dateto, 0);
                }
                $difference = $dateto - $datefrom; // Difference in seconds
                //-- Year check and adjustment
                if( floor($difference / 31536000) > 0){
                    $diff['years'] = floor($difference / 31536000);
                    $difference -= floor($difference / 31536000)*31536000;
                } else { $diff['years']=null; }
                //@TODO update this to a more accurate calculation (strftime('%y%m'))
                //-- Month check and adjustment
                if(floor($difference / 2678400) > 0){
                    $diff['months'] = floor($difference / 2678400);
                    $difference -=    floor($difference / 2678400)*2678400;
                } else { $diff['months']=null; }
                //-- Day check and adjustment
                if(floor($difference / ((60 * 60)*24)) > 0){
                    $diff['days'] = floor($difference / ((60 * 60)*24));
                    $difference -=  floor($difference / ((60 * 60)*24))*((60 * 60)*24);
                } else { $diff['days']=null; }
                //-- Hours check and adjustment
                if(floor($difference / (60 * 60)) > 0){
                    $diff['hours'] = floor($difference / (60 * 60));
                    $difference -=   floor($difference / (60 * 60))*(60 * 60);
                } else { $diff['hours']=null; }
                //-- Minutes check and adjustment
                if(floor($difference / 60) > 0){
                    $diff['minutes'] = floor($difference / 60);
                    $difference -=     floor($difference / 60)*60;
                } else { $diff['minutes']=null; }
                //-- Seconds, that should be all we have left
                $diff['seconds'] = $difference;
                
                return $diff;
        }
        public function makeEventDetail($events=array(),$occurance=0, $tpls=array()){
            $tpls = (object)$tpls;
            if(count($events)){
                $occ=0;
                foreach($events AS $e){
                    if($occ == $occurance || ($occurance === 0 && $occ ==0)){
                        $detailPH = $e[0];
                        $detailPH['allplaceholders'] = implode(', ',array_keys($e[0]));
                        $o = $this->getChunk($tpls->tplDetail,$detailPH);
                            break;
                    }
                    $occ++;
                }
            } else { return 'No Details'; }
            return $o;
        }
        public function makeEventList($limit=5, $events=array(),$tpls=array()){
            $o = '';
            $tpls = (object)$tpls;
            if(count($events)){
                $preHead = '';
                $i=0;
                foreach($events AS $e){
                    //-- Now we need to loop all occurances on a single date
                    $rvar=0;
                    do {
                        if(strftime('%b',$e[$rvar]['startdate']) != $preHead && !empty($tpls->tplElMonthHeading)){
                            // Load list heading
                            $o.= $this->getChunk($tpls->tplElMonthHeading,$e[$rvar]);
                            $preHead = strftime('%b',$e[$rvar]['startdate']);
                        }
                        $o .= $this->getChunk($tpls->tplElItem,$e[$rvar]);
                        $i++;
                        $rvar++;
                        
                    } while ($rvar < count($e) && $i < $limit);
                    if($i >= $limit) break;
                }
            } else { return 'No Events'; }
            return $this->getChunk($tpls->tplElWrap, array('eventList'=>$o));
        }
        public function getEventCalendarDateRange($activeMonthOnlyEvents=false){
            $startDate = $_REQUEST['dt'] ? $_REQUEST['dt'] : strftime('%Y-%m');
            $mStartDate = strftime('%Y-%m',strtotime($startDate)) . '-01 00:00:01';
            $nextMonth = strftime('%Y-%m', strtotime('+1 month',strtotime($mStartDate)));
            $prevMonth = strftime('%Y-%m', strtotime('-1 month',strtotime($mStartDate)));
            $startDOW = strftime('%u', strtotime($mStartDate));
            $lastDayOfMonth = strftime('%Y-%m',strtotime($mStartDate)) . '-'.date('t',strtotime($mStartDate)) .' 23:59:59';
            $startMonthCalDate = $startDOW <= 6 ? strtotime('- '.$startDOW.' day', strtotime($mStartDate)) : strtotime($mStartDate)	;
            $endMonthCalDate = strtotime('+ 6 weeks', $startMonthCalDate);
            if($debug) echo 'Active Month Only: '.$mStartDate.' :: '.$lastDayOfMonth.'  All displayed dates: '.strftime('%Y-%m-%d',$startMonthCalDate).' :: '.strftime('%Y-%m-%d',$endMonthCalDate).'<br />';
            if($activeMonthOnlyEvents) return array('start'=>strtotime($mStartDate), 'end'=>strtotime($lastDayOfMonth)); else return array('start'=>$startMonthCalDate, 'end'=>$endMonthCalDate);
        }
        public function makeEventCalendar($events=array(),$resourceId=null,$tpls=array('event'=>'month.inner.container.row.day.eventclean','day'=>'month.inner.container.row.day','week'=>'month.inner.container.row','month'=>'month.inner.container','heading'=>'month.inner.container.row.heading')){
            $startDate = $_REQUEST['dt'] ? $_REQUEST['dt'] : strftime('%Y-%m-%d');
            $mStartDate = strftime('%Y-%m',strtotime($startDate)) . '-01 00:00:01';
            $mCurMonth = strftime('%m', strtotime($mStartDate));
            $nextMonth = strftime('%Y-%m', strtotime('+1 month',strtotime($mStartDate)));
            $prevMonth = strftime('%Y-%m', strtotime('-1 month',strtotime($mStartDate)));
            $startDOW = strftime('%u', strtotime($mStartDate));
            $lastDayOfMonth = strftime('%Y-%m',strtotime($mStartDate)) . '-'.date('t',strtotime($mStartDate)) .' 23:59:59';
            $endDOW = strftime('%u', strtotime($lastDayOfMonth));
            $tpls=(object)$tpls;
            $out = '';
            $headings_arr = array(); //@TODO this can be removed
            $startMonthCalDate = $startDOW <= 6 ? strtotime('- '.$startDOW.' day', strtotime($mStartDate)) : strtotime($mStartDate)	;
            $endMonthCalDate = strtotime('+ '.(6 - $endDOW).' day', strtotime($lastDayOfMonth));
            //------//
            $headingLabel = strtotime($mStartDate);
            $todayLink = $this->modx->makeUrl($resourceId,'',array('dt' => strftime('%Y-%m')));
            $prevLink = $this->modx->makeUrl($resourceId,'',array('dt' => $prevMonth));
            $nextLink = $this->modx->makeUrl($resourceId,'',array('dt' => $nextMonth));
            
            $chunkEvent = $this->loadChunk($tpls->event);
            $chunkDay = $this->loadChunk($tpls->day);
            $chunkWeek = $this->loadChunk($tpls->week);
            $chunkMonth = $this->loadChunk($tpls->month);
            
            $heading = '';
            for($i=0;$i<7;$i++){
                    if($debug) echo '&nbsp;&nbsp;'.strftime('%A ', strtotime('+ '.$i.' day', $startMonthCalDate)).'<br />';
                    $heading.=$this->getChunk($tpls->heading, array('dayOfWeekId'=>'','dayOfWeekClass'=>'mxcdow', 'dayOfWeek'=>strftime('%a ', strtotime('+ '.$i.' day', $startMonthCalDate))));
            }
            //-- Set additional day placeholders for week
            $phHeading = array(
                'weekId'=>''
                ,'weekClass'=>''
                ,'days'=>$heading 
                );
            //$weeks.=$chunkWeek->process($phWeek);
            $heading=$this->getChunk($tpls->week, $phHeading);

            $weeks = '';
            //-- Start the Date loop
            $var=0;
            do {
                if($debug) echo '---------------<br />';
                if($debug) echo 'Week '.($var + 1).'<br />';
                if($debug) echo '---------------<br />';
                // Week Start date
                $iWeek = strtotime('+ '.$var.' week', $startMonthCalDate);
                $diw = 0;
                $days = '';
                do{
                    // Get the week's days
                    $iDay = strtotime('+ '.$diw.' day', $iWeek);
                    if($debug) echo strftime('%a %b %e', $iDay).'<br />';
                    $eventList = '';
                    if(count($events[strftime('%Y-%m-%d', $iDay)])){
                        //-- Echo each event item
                        $e = $events[strftime('%Y-%m-%d', $iDay)];
                        
                        foreach($e AS $el){
                            if($debug) echo '&nbsp;&nbsp;<span style="color:green;">++</span>&nbsp;&nbsp;'.$el['title'].'<br />';
                            //$eventList.=$chunkEvent->process($el);
                            $eventList.=$this->getChunk($tpls->event, $el);
                        }
                    } else { if($debug) echo '&nbsp;&nbsp;<span style="color:red;">--&nbsp;&nbsp;'.strftime('%m-%d', $iDay).'</span><br />'; }
                    //-- Set additional day placeholders for day
                    $thisMonth = strftime('%m', $iDay);
                    $isToday = strftime('%m-%d') == strftime('%m-%d', $iDay) ? 'today ' : '';
                    $phDay = array(
                        'dayOfMonth'=>(strftime('%e',$iDay) == 1 ? strftime('%b %e',$iDay) : strftime('%e',$iDay))
                        ,'dayOfMonthID'=>strftime('%A%d',$iDay)
                        ,'events'=>$eventList 
                        ,'class'=>($mCurMonth == $thisMonth ? $isToday.(!empty($eventList) ? 'hasEvents' : 'noEvents') : 'ncm')
                        );
                    //$days.=$chunkDay->process($phDay);
                    $days.=$this->getChunk($tpls->day, $phDay);
                } while (++$diw < 7);
                if($debug) echo '<br />';
                //-- Set additional day placeholders for week
                $phWeek = array(
                    'weekId'=>'mxcWeek'.$var
                    ,'weekClass'=>strftime('%A%d',$iDay)
                    ,'days'=>$days 
                    );
                //$weeks.=$chunkWeek->process($phWeek);
                $weeks.=$this->getChunk($tpls->week, $phWeek);
            } while (++$var < 6); //Only advance 5 weeks giving total of 6 weeks
            //-- Set additional day placeholders for month
            $phMonth = array(
                'containerID'=>strftime('%a',$iDay)
                ,'containerClass'=>strftime('%a%Y',$iDay)
                ,'weeks'=>$heading.$weeks 
                ,'headingLabel'=>$headingLabel
                ,'todayLink'=>$todayLink
                ,'todayLabel'=> $this->modx->lexicon('mxcalendars.label_today')
                ,'prevLink'=>$prevLink
                ,'nextLink'=>$nextLink
                );
            //return $chunkMonth->process($phMonth);
            return $this->getChunk($tpls->month, $phMonth);
        }

}


?>
