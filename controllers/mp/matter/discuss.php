<?php
require_once dirname(__FILE__).'/matter_ctrl.php';

class discuss extends mp_controller {

    public function get_access_rule()
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';
        return $rule_action;
    }
    /**
     *
     */
    public function index_action() 
    {
        $q = array(
            'wid id,title', 
            'xxt_wall',
            "mpid='$this->mpid'"
        );
        $q2 = array('o'=>'create_at desc');

        $walls = $this->model()->query_objs_ss($q, $q2);

        return new ResponseData($walls);
    }
}