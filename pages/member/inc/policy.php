<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="content-header">
    <p class="text-muted mb-0"><a class="text-dark me-2" href="/affiliate"><i class="fa-solid fa-chevron-left me-3"></i></a>นโยบายและข้อกำหนดของโปรแกรมพันธมิตร</p>
</div>
<div class="bg-white p-4 mb-4" style="line-height: 28px;">
    <div class="row">
        <div class="row col-lg-5" style="max-width: 1000px; margin: 0 auto;">
            <?=get_option("affiliate_instructions")?>
        </div>
        <div class="col-lg-7">
            <?=get_option("affiliate_policy")?>
            <?=get_option("affiliate_requirements_and_conditions")?>
        </div>
    </div>
</div>