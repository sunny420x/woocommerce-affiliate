<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="d-flex gap-2 card card-custom p-4">
    <div id="policy">
        <?=get_option("affiliate_policy")?>
    </div>
    <div id="requirements">
        <?=get_option("affiliate_requirements_and_conditions")?>
    </div>
</div>
<div class="d-flex gap-2">
    <?=get_option("affiliate_instructions")?>
</div>