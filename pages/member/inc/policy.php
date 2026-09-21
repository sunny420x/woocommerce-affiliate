<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="row">
    <div class="card card-custom p-4 col-6" id="policy">
        <?=get_option("affiliate_policy")?>
    </div>
    <div class="card card-custom p-4 col-6" id="requirements">
        <?=get_option("affiliate_requirements_and_conditions")?>
    </div>
</div>