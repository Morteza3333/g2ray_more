<?php
/**
 * Provide a Persian guide inside the admin panel
 *
 * @link       https://example.com
 * @since      1.0.0
 * @package    Festival_Winners_Showcase
 * @subpackage Festival_Winners_Showcase/admin/partials
 */
?>

<div class="wrap fws-help-wrap" style="direction: rtl; max-width: 1000px; margin-top: 30px;">
    <div style="background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
        <h1 style="color: #c5a059; font-size: 32px; margin-bottom: 30px;">📸 راهنمای کار با پلاگین نمایش برندگان جشنواره</h1>

        <section style="margin-bottom: 40px;">
            <h2 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">🥇 نحوه افزودن برنده</h2>
            <p>برای افزودن یک برنده جدید، مراحل زیر را دنبال کنید:</p>
            <ul style="list-style-type: disc; margin-right: 25px;">
                <li>به منوی <strong>برندگان جشنواره</strong> بروید و <strong>افزودن جدید</strong> را انتخاب کنید.</li>
                <li>عنوان (نام اثر یا برنده) را وارد کنید.</li>
                <li>در بخش <strong>جزئیات برنده</strong>، رتبه (اول تا سوم)، نام عکاس و آیدی اینستاگرام را وارد کنید.</li>
                <li><strong>تصویر شاخص</strong> را به عنوان تصویر اصلی اثر انتخاب کنید.</li>
                <li>اگر برنده در دسته‌بندی <strong>مجموعه‌عکس</strong> است، می‌توانید تصاویر دیگر مجموعه را در بخش گالری آپلود کنید.</li>
                <li>دسته‌بندی مربوطه (تک‌عکس یا مجموعه‌عکس) را از ستون سمت چپ انتخاب کنید.</li>
            </ul>
        </section>

        <section style="margin-bottom: 40px;">
            <h2 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">🎨 سفارشی‌سازی ظاهر</h2>
            <p>برای تغییر رنگ‌بندی و استایل گالری:</p>
            <ul style="list-style-type: disc; margin-right: 25px;">
                <li>به منوی <strong>تنظیمات جشنواره</strong> بروید.</li>
                <li>رنگ اصلی و رنگ تاکید را متناسب با قالب سایت خود انتخاب کنید.</li>
                <li>حالت تیره یا روشن را برای پس‌زمینه گالری انتخاب کنید.</li>
                <li>بر روی دکمه ذخیره کلیک کنید تا تغییرات به صورت آنی اعمال شوند.</li>
            </ul>
        </section>

        <section style="margin-bottom: 40px;">
            <h2 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">🔢 جابجایی و ترتیب</h2>
            <p>ترتیب نمایش برندگان در سایت بر اساس ترتیبی است که در پنل مدیریت چیده شده‌اند:</p>
            <ul style="list-style-type: disc; margin-right: 25px;">
                <li>در لیست برندگان، می‌توانید سطرها را با <strong>کشیدن و رها کردن (Drag & Drop)</strong> جابجا کنید.</li>
                <li>ترتیب جدید به صورت خودکار ذخیره می‌شود.</li>
            </ul>
        </section>

        <section style="margin-bottom: 40px;">
            <h2 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">💻 نمایش در سایت</h2>
            <p>برای نمایش گالری برندگان در هر کجای سایت (برگه یا نوشته)، از کد کوتاه زیر استفاده کنید:</p>
            <code style="display: block; background: #f0f0f0; padding: 15px; border-right: 5px solid #c5a059; font-size: 18px; text-align: left; direction: ltr;">[festival_winners]</code>
            <p style="margin-top: 15px;">همچنین می‌توانید برای فیلتر کردن بر اساس دسته‌بندی خاص از ویژگی <code>category</code> استفاده کنید:</p>
            <code style="display: block; background: #f0f0f0; padding: 15px; border-right: 5px solid #c5a059; font-size: 18px; text-align: left; direction: ltr;">[festival_winners category="single-photo"]</code>
        </section>

        <div style="background: #fff8e5; padding: 20px; border-radius: 8px; border: 1px solid #ffeeba;">
            <p style="margin: 0;">✨ <strong>نکته:</strong> برای بهترین نتیجه بصری، سعی کنید تصاویر با کیفیت بالا و ابعاد مناسب آپلود کنید. پلاگین به صورت خودکار چیدمان را بهینه‌سازی می‌کند.</p>
        </div>
    </div>
</div>
