<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="row card card-custom mb-4 p-4">
    <div class="col-lg-6" id="policy">
        <?=get_option("affiliate_policy")?>
    </div>
    <div class="col-lg-6" id="requirements">
        <?=get_option("affiliate_requirements_and_conditions")?>
    </div>
</div>
<div class="card card-custom mb-4 d-flex gap-2">
    <?=get_option("affiliate_instructions")?>
</div>