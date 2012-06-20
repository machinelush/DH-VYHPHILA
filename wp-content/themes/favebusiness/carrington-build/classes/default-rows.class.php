<?php

function cfct_build_default_rows_init() {
	do_action('cfct-rows-loaded');
}
add_action('init', 'cfct_build_default_rows_init', 2);

?>