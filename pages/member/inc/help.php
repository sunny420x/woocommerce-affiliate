<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="content-header">
    <p class="text-white mb-0"><a class="text-white me-2" href="/affiliate"><i class="fa-solid fa-chevron-left me-3"></i></a>ศูนย์ช่วยเหลือ (Help Center)</p>
</div>
<div class="bg-white p-4" id="help">
    <?=get_option('affiliate_support_page')?>
</div>