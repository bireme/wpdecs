<?php

function get_qualifiers() {
    $QL = array();
    $QL['p'] = array();
    $QL['i'] = array();
    $QL['e'] = array();

    $contents = file("qualifiers.txt", FILE_USE_INCLUDE_PATH | FILE_SKIP_EMPTY_LINES);
    sort($contents);

    foreach ($contents as $key => $value) {
        $v = str_replace('/', '', utf8_encode(rtrim($value)));
        $res = explode('@', $v);

        $QL['i'][$res[3]] = $res[0];
        $QL['e'][$res[3]] = $res[1];
        $QL['p'][$res[3]] = $res[2];
    }

    return $QL;
}

function get_descriptors_by_words($words, $lang = ""){

    $words = trim($words);
    $definition_len = 10000000;
    $QUALIFIER_LIST = get_qualifiers();

    if(!array_key_exists($lang, $QUALIFIER_LIST)) {
        $QUALIFIER_LIST = $QUALIFIER_LIST['p'];
    } else {
        $QUALIFIER_LIST = $QUALIFIER_LIST[$lang];
    }

    $xmlFile = get_descriptors_from_decs( 'http://decs.bvsalud.org/cgi-bin/mx/cgi=@vmx/decs/?words=' . urlencode($words) . "&lang=" . $lang );
    $xmlTree = $xmlFile->xpath("/decsvmx/decsws_response");

    // print 'http://decs.bvsalud.org/cgi-bin/mx/cgi=@vmx/decs/?words=' . urlencode($words) . "&lang=" . $lang;

    $descriptors = array();
    foreach($xmlTree as $node){

        $definition = "";
        if((string) $node->record_list->record->definition->occ['n'])
            $definition = (string) $node->record_list->record->definition->occ['n'];

        $qid = array();
        $qualifiers = array();
        foreach($node->record_list->record->allowable_qualifier_list->allowable_qualifier as $qualifier) {
            if(array_key_exists((string) $qualifier, $QUALIFIER_LIST)) {
                $qid[(string) $qualifier] = (int) $qualifier['id'];
                $qualifiers[(string) $qualifier] = $QUALIFIER_LIST[(string) $qualifier];
            }
        }

        // print_r($qualifiers);

        // description size
        if(strlen($definition) >= $definition_len) {
            $definition = substr($definition, 0, $definition_len-3) . "...";
        }

        // langs
        $langs = array();
        foreach($node->record_list->record->descriptor_list->descriptor as $descriptor) {
            $langs[(string) $descriptor['lang']] = (string) $descriptor;
        }

        // mfn
        $mfn = (int) $node->record_list->record['mfn'];

        $leaf = true;
        // send the leaf information to de descriptor
        if($node->tree->self->term_list->term['leaf'] != "true") {
            $leaf = false;
        }

        // tree id
        $descriptors[(string) $node->tree->self->term_list->term] = array(
            'tree_id' => (string) $node['tree_id'],
            'definition' => $definition,
            'qid' => $qid,
            'qualifiers' => $qualifiers,
            'lang' => $langs,
            'synonym' => false,
            'mfn' => $mfn,
            'is_leaf' => $leaf,
        );

        foreach($node->record_list->record->synonym_list->synonym as $synonym) {
            $descriptors[ (string) $synonym  ] = array(
                'tree_id' => (string) $node['tree_id'],
                'definition' => $definition,
                'qid' => $qid,
                'qualifiers' => $qualifiers,
                'lang' => $langs,
                'synonym' => true,
                'mfn' => $mfn,
            );
        }
    }

    return array('descriptors'=>$descriptors);

}

function get_descriptors_lang_by_tree_id($id) {

    $xmlFile = get_descriptors_from_decs( 'http://decs.bvsalud.org/cgi-bin/mx/cgi=@vmx/decs/?tree_id=' . urlencode($id) );
    $xmlTree = $xmlFile->xpath("/decsvmx/decsws_response/record_list");

    $result = array();
    foreach($xmlTree as $node) {

        foreach($node->record->descriptor_list->descriptor as $descriptor) {
            $result[(string) $descriptor['lang']] = (string) $descriptor;
        }
    }

    return $result;
}

function get_descriptors_from_decs( $queryUrl ){

    // use the curl as default
    if ( function_exists('curl_version') ){

        $ch = curl_init();
        $timeout = 5; // set to zero for no timeout
        curl_setopt ( $ch, CURLOPT_URL, $queryUrl);
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        $file_contents = curl_exec($ch);
        curl_close($ch);

    $xmlFile = new SimpleXMLElement($file_contents);

    // if dont have the curl use the simplexml_load_file to load the decs xml
    } elseif ( function_exists('simplexml_load_file') == "Enabled" ){

        $xmlFile = simplexml_load_file( $queryUrl );

    } else {
        Throw new Exception('This module need simplexml or curl to get the descriptors from bvs.salude.');
    }

    return $xmlFile;

}

function get_the_wpdecs_terms($id=false) {

    global $post;
    $post_id = $post->ID;
    if($id)
        $post_id = $id;


    $wpdecs_terms = get_post_meta($post_id, 'wpdecs_terms', true);
    if($wpdecs_terms) {
        return $wpdecs_terms;
    }

    return array();
}

function the_wpdecs_terms() {

    print '<div class="wpdecs_terms">';
    print '<h2>' . __('DeCS Terms') . '</h2>';
    print '<ul>';


    foreach(get_the_wpdecs_terms() as $term) {

        // print "<pre>";
        // var_dump($term);

        $print_ql = "";
        if(isset($term['qualifier'])) {
            foreach($term['qualifier'] as $ql) {
                $print_ql .= $ql . '/';
            }

            $print_ql = trim($print_ql, "/");
            $print_ql = "($print_ql)";
        }

        print "<li>${term['term']} $print_ql</li>";

    }

    print '</ul>';
    print '</div>';
}
