<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="card card-custom p-4 mb-4" id="settings">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-building-columns text-primary me-2"></i>ตั้งค่าบัญชีธนาคารสำหรับรับเงิน</h5>
    <p class="text-muted mb-0">กรุณากรอกข้อมูลบัญชีธนาคารของคุณเพื่อให้เราสามารถโอนเงินค่าคอมมิชชั่นให้คุณได้อย่างถูกต้อง</p>
    <form action="" method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-medium">หมายเลขบัญชีธนาคาร:</label>
            <input type="text" name="aff_account_number" class="form-control" 
                    value="<?= esc_attr($user_affiliate_info->bank_account_number ?? ''); ?>" 
                    placeholder="ระบุเลขบัญชีธนาคาร" required />
        </div>

        <div class="col-md-6">
            <label class="form-label fw-medium">ธนาคาร:</label>
            <select name="aff_bank_name" class="form-select">
                <?php
                $banks = ["ธนาคารกรุงเทพ", "ธนาคารกสิกรไทย", "ธนาคารไทยพาณิชย์", "ธนาคารกรุงไทย", "ธนาคารกรุงศรีอยุธยา", "ธนาคารทหารไทยธนชาต"];
                foreach ($banks as $bank):
                    $selected = selected($user_affiliate_info->bank_name ?? '', $bank, false);
                    echo "<option value='{$bank}' {$selected}>{$bank}</option>";
                endforeach;
                ?>
            </select>
        </div>

        <div class="col-12 mt-4">
            <button type="submit" name="save_affiliate_info" class="btn btn-primary px-4">
                <i class="fa-regular fa-floppy-disk me-1"></i> บันทึกข้อมูลบัญชี
            </button>
        </div>
    </form>
    <br>
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-building-columns text-primary me-2"></i>ตั้งค่าข้อมูลส่วนตัว</h5>
    <div class="table-responsive">
        <table class="table">
            <tbody>
                <tr>
                    <th>ข้อมูลส่วนตัว</th>
                    <td><button class="btn btn-outline-primary" onclick="window.open('/my-account/edit-account/')">จัดการ</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>