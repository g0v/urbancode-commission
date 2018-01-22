<?php
function petition_parse($petition_array) {
    global $petitionPack;
    global $petitionTableTitle;
    $petition_title = $petitionPack->getTitleString();
    $petition_tag = array('reason', 'suggest', 'response', 'adhoc', 'resolution');

    if(preg_match("/$petitionTableTitle/", $petition_array[0])) {
        unset($petition_array[0]);
        $petition_array = array_values($petition_array);
    }
    //依'編號'切割petition array
    $petition_cnt = find_index($petition_array, $petitionPack->petition_num);
    $petition_case = clean_empty(slice_my_array($petition_array, $petition_cnt));
    if(count($petition_case) ==0 ) return;
    //如果petition_case[0]是案名，存入petition_header
    if(preg_match("/案名/", $petition_case[0][0])) {
        $petition_header = $petition_case[0];
        unset($petition_case[0]);
    }
    //移除陳情文中的案名line
    if(isset($petition_header)) {
        foreach($petition_case as &$petition_active) {
            foreach($petition_active as $active_k => $active_line) {
                foreach($petition_header as $header_k => $header_line) {
                    if($active_line === $header_line) unset($petition_active[$active_k]);
                }
            }
            $petition_active = array_values($petition_active);
        }
        $petition_case_name = combine_array_sentence(array($petition_header))[0];
    }

    $petition_output = array();
    foreach($petition_case as $k => $peition) {
        if(isset($petition_case_name)) $petition_this['petition_case'] = $petition_case_name;
        $petition_index = find_index($peition, $petition_title);
        $petition_section = clean_empty(slice_my_array($peition, $petition_index));

        foreach($petition_section as $single_petition) {
            if (preg_match("/$petitionPack->petition_num/", $single_petition[0])) {
                $first_line = $single_petition[0];
                $name_pos = strpos($first_line, "陳情人");
                $petition_num = substr($first_line, 0, $name_pos);
                $petition_num = trim(preg_replace("/$petitionPack->petition_num/", "", $petition_num));
                $petition_this['petition_num'] = $petition_num;
                $petition_name = substr($first_line, $name_pos);
                $petition_name = trim(preg_replace("/陳情人/", "", $petition_name));
                $petition_this['name'] = $petition_name;
            } else {
                $tag = $petitionPack->pregTag($single_petition[0]);
                if ($tag === "petition_num|name") continue;
                if ($tag != 'not found') $petition_this[$tag] = section_parse($single_petition);
            }
        }
        if(isset($petition_this)) {
            array_push($petition_output, $petition_this);
            unset($petition_this);
        }
    }
    return($petition_output);
}
