<?php
if(!defined('QWP_ROOT')){exit('Invalid Request');}

// template function, you need to modify it if you want to use
function qwp_get_visitor_acls(&$acls) {
    $acls = array(
        'modules' => array(
            'portal' => 1,
        ),
        'pages' => array(
            'portal' => array(
                'sample' => 1,
            ),
        ),
    );
}
/*
function qwp_get_role_acls(&$acls, $role_id) {
    $acls = array(
        'modules' => array(
            'portal' => 1,
            'sample' => 'Samples',
            'users' => 'Users',
            'settings' => 'Settings',
            'help' => 'Help',
            'sample-sub' => 1,
            'sample-sub-sub' => 1,
        ),
        'pages' => array(
            'portal' => array(
                'sample' => 1,
            ),
            'sample' => array(
                'form' => 'Form sample',
                'table' => 'Table sample',
            ),
            'sample-sub' => array(
                'test' => 1,
            ),
        ),
        'ops' => array(
            'sample#form' => array(
                'edit' => 1,
            ),
            'sample#table' => array(
                'list' => 1,
                'get_types' => 1,
            ),
            'users' => array(
                'list' => 1,
                'add' => 1,
                'edit' => 1,
                'del' => 1,
            ),
        ),
    );
}*/
function qwp_get_all_acls(&$acls) {

}
function qwp_get_user_acls(&$acls) {
    global $USER;

    $q = db_select('sys_modules', 'm');
    $q->fields('m', array('name', 'parent', 'id', 'type', 'identity'));
    if (!qwp_user_is_admin()) {
        $q->leftJoin('sys_role_modules', 'r', 'r.module_id=m.id');
        $q->condition('role_id', $USER->role);
    }
    $q->condition('enabled', 'y')->orderBy('parent', 'asc')->orderBy('seq', 'asc');
    $ret = $q->execute();
    $acls['modules'] = array();
    $acls['pages'] = array();
    $acls['ops'] = array();
    $identities = array(
        '0-' => '',
    );
    $modules = &$acls['modules'];
    $pages = &$acls['pages'];
    $ops = &$acls['ops'];
    $is_page = array();
    while (($r = $ret->fetchAssoc())) {
        $identity = &$r['identity'];
        $parent = &$r['parent'];
        $parent_path = isset($identities[$parent]) && $identities[$parent] ? $identities[$parent] . '-' : '';
        $identity_key = $parent . $r['id'] . '-';
        if ($r['type'] === 'm') {
            $modules[$parent_path . $identity] = L($r['name']);
        } else if ($r['type'] === 'p') {
            $path = $identities[$parent];
            if (!isset($pages[$path])) $pages[$path] = array();
            $pages[$path][$identity] = L($r['name']);
            $is_page[$identity_key] = 1;
        } else if ($r['type'] === 'op') {
            $path = $identities[$parent];
            if (isset($is_page[$parent])) {
                $pos = strrpos($path, '-');
                $path[$pos] = '#';
            }
            if (!isset($ops[$path])) $ops[$path] = array();
            $ops[$path][$identity] = $r['name'] ? L($r['name']) : 1;
        }
        $identities[$identity_key] = $parent_path . $identity;
    }
}
function qwp_init_nav_modules(&$acls) {
    $modules = array();
    $sub_modules = array();
    $all_modules = &$acls['modules'];
    $left_modules = array();
    foreach($all_modules as $m => $desc) {
        $arr = explode('-', $m);
        $level = count($arr);
        if ($level === 1) {
            if (file_exists(join_paths(QWP_MODULE_ROOT, $m, 'home.php'))) {
                $modules[$m] = $desc;
            } else {
                $left_modules[$m] = $desc;
            }
        } else if ($level === 2) {
            if (isset($left_modules[$arr[0]]) && file_exists(join_paths(QWP_MODULE_ROOT, implode('/', $arr), 'home.php'))) {
                // select the first module
                $modules[$m] = $left_modules[$arr[0]];
                unset($left_modules[$arr[0]]);
            }
            if (!isset($sub_modules[$arr[0]])) $sub_modules[$arr[0]] = array();
            $sub_modules[$arr[0]][$m] = array('desc' => $desc);
        } else if ($level === 3) {
            if (isset($left_modules[$arr[0]]) && file_exists(join_paths(QWP_MODULE_ROOT, implode('/', $arr), 'home.php'))) {
                // select the first module
                $modules[$m] = $left_modules[$arr[0]];
                unset($left_modules[$arr[0]]);
            }
            $parent = $arr[0] . '-' . $arr[1];
            if (!isset($sub_modules[$arr[0]][$parent]['sub'])) $sub_modules[$arr[0]][$parent]['sub'] = array();
            $sub_modules[$arr[0]][$parent]['sub'][] = array($m, $desc);
        }
    }
    _C('nav', $modules);
    _C('sub_nav', $sub_modules);
}
function qwp_has_sub_modules() {
    global $MODULE;

    $nav = C('sub_nav', array());
    return isset($nav[$MODULE[0]]);
}
// template function, you need to modify it if you want to use
function qwp_init_security(&$acls) {
    $acls = array();
    qwp_get_user_acls($acls);
    _C('acls', $acls);
    qwp_init_nav_modules($acls);
}
function qwp_doing_security_check() {
    global $MODULE_URI, $PAGE, $OP;

    if (qwp_is_passport_module()) {
        return true;
    }
    $acls = C('acls', null);
    if (!$acls) {
        qwp_init_security($acls);
    }
    if (!isset($acls['modules'][$MODULE_URI])) {
        return false;
    }
    if ($OP) {
        $path = $MODULE_URI;
        if ($PAGE) {
            $path .= '#' . $PAGE;
        }
        return isset($acls['ops'][$path]) && isset($acls['ops'][$path][$OP]);
    }
    if ($PAGE) {
        return isset($acls['pages'][$MODULE_URI]) && isset($acls['pages'][$MODULE_URI][$PAGE]);
    }
    log_info('security check is passed: ' . $MODULE_URI);
}