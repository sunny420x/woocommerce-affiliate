<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="card card-custom p-4 mb-4">
    <div class="row">
        <div class="row col-lg-5" style="max-width: 1000px; margin: auto;">
            <?=get_option("affiliate_instructions")?>
        </div>
        <div class="col-lg-7">
            <?=get_option("affiliate_policy")?>
            <?=get_option("affiliate_requirements_and_conditions")?>
        </div>
    </div>
</div>