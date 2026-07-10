<?php include "nav.php"; ?>
<?php
$dbHost = "localhost";
$dbUsername = "shaamdan_user";
$dbPassword = "w56OXGMj1X5ck2ny";
$dbName = "shaamdan_jashnvare";

// Create database connection
$conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);
mysqli_set_charset($conn, "utf8mb4");

$users = 0;
if(isset($_GET["type"]) && $_GET["type"] == 'show') {
    $users = mysqli_real_escape_string($conn, $_GET["id_user"]);
    echo "<script>
            window.addEventListener('DOMContentLoaded', function(){
                $('#slider_multiaks').modal('show');
            });
        </script>";
}

// آماده‌سازی و اجرای کوئری‌ها در بالای صفحه برای تفکیک رتبه‌های برتر همراه با دریافت تصویر شاخص مجموعه
$searchid = isset($_POST['idsearch']) ? mysqli_real_escape_string($conn, $_POST['idsearch']) : "";
if(isset($_POST['search_user']) && $searchid != "") {
    $sql = "SELECT dm.*, uia.image_url, SUM(dm.mark_pictures) AS sum_mark, SUM(dm.rank_pictures) AS sum_rank
            FROM davari_multipic dm
            LEFT JOIN upload_image_address uia ON dm.id_user = uia.id_user
            WHERE dm.id_user='$searchid'
            GROUP BY dm.id_user
            ORDER BY sum_mark DESC";
} else {
    $sql = "SELECT dm.*, uia.image_url, SUM(dm.mark_pictures) AS sum_mark, SUM(dm.rank_pictures) AS sum_rank
            FROM davari_multipic dm
            LEFT JOIN upload_image_address uia ON dm.id_user = uia.id_user
            GROUP BY dm.id_user
            ORDER BY sum_mark DESC, sum_rank DESC";
}

$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_all($result, MYSQLI_ASSOC);

// تفکیک ۳ نفر اول برای ساخت پودیوم قهرمانی آثار مجموعه‌ای
$top3 = array_slice($data, 0, 3);
$others = array_slice($data, 3);
?>

<style>
    @font-face {
        font-family: 'Vazir';
        src: url('https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/Vazir.woff2') format('woff2');
    }

    body {
        font-family: 'Vazir', Tahoma, sans-serif;
        background-color: #f1f5f9;
        color: #1e293b;
        margin: 0;
        padding: 0;
    }

    .page-wrapper {
        min-height: 100vh;
        padding: 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        padding: 15px 25px;
        border-radius: 15px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        border-right: 8px solid #059669;
    }

    .page-title {
        color: #1e293b;
        font-weight: 800;
        margin: 0;
        font-size: 1.35rem;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-title { font-weight: 800; color: #065f46; border-right: 5px solid #10b981; padding-right: 15px; margin-top: 35px; margin-bottom: 25px; font-size: 1.3rem; }
    .section-title:first-of-type { margin-top: 10px; }

    /* ترنزیشن‌های انیمیشنی فوق‌العاده نرم و روان */
    .btn, .custom-table tr, .info-box, .winner-card, .winner-avatar-wrap, .judged-slide img, .slider-nav-btn {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-excel { background-color: #10b981; }
    .btn-pdf { background-color: #ef4444; }
    .btn-search { background-color: #059669; color: white; height: 45px; width: 100%; justify-content: center; }

    .card-custom {
        background: white;
        padding: 25px;
        border-radius: 15px;
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    .form-label-small {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 8px;
        display: block;
    }

    .form-control-custom {
        height: 45px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        padding: 0 15px;
        width: 100%;
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    .form-control-custom:focus {
        border-color: #059669;
        outline: none;
        box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
    }

    .column-selector {
        background: #f8fafc;
        padding: 15px 25px;
        border-radius: 15px;
        border: 1px solid #e2e8f0;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .column-selector h6 {
        color: #64748b;
        font-weight: 800;
        margin: 0;
        font-size: 0.9rem;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .checkbox-item {
        cursor: pointer;
        font-weight: 600;
        color: #334155;
        display: inline-flex;
        align-items: center;
        font-size: 0.85rem;
        background: white;
        padding: 6px 12px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    .checkbox-item:hover { border-color: #059669; background: #f0fdf4; }
    .checkbox-item input { margin-left: 8px; accent-color: #059669; width: 18px; height: 18px; }

    .table-wrapper {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }

    /* پودیوم نفرات اول تا سوم مجموعه عکس */
    .podium-container { display: flex; justify-content: center; align-items: flex-end; gap: 25px; margin-bottom: 45px; margin-top: 20px; flex-wrap: wrap; }
    .winner-card { background: #fff; border-radius: 24px; padding: 25px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.02); position: relative; border: 2px solid #e2e8f0; width: 100%; max-width: 280px; }
    .winner-card:hover { transform: translateY(-12px); box-shadow: 0 20px 35px rgba(16, 185, 129, 0.12); }

    .winner-avatar-wrap { width: 95px; height: 95px; background: #e6f4ea; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; border: 4px solid #fff; box-shadow: 0 6px 15px rgba(0,0,0,0.06); }
    .winner-icon { font-size: 2.6rem; color: #10b981; }
    .medal-badge { position: absolute; top: -12px; right: -12px; width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.3rem; box-shadow: 0 6px 15px rgba(0,0,0,0.15); z-index: 10; }

    /* شخصی‌سازی گرافیکی رتبه‌ها */
    .rank-1 { order: 2; transform: scale(1.08); background: linear-gradient(180deg, #fff 50%, #f0fdf4 100%); border-color: #10b981; }
    .rank-1 .medal-badge { background: linear-gradient(135deg, #10b981, #059669); }
    .rank-1 .winner-avatar-wrap { background: #d1fae5; }
    .rank-2 { order: 1; background: linear-gradient(180deg, #fff 60%, #f8fafc 100%); border-color: #cbd5e1; }
    .rank-2 .medal-badge { background: linear-gradient(135deg, #94a3b8, #64748b); }
    .rank-2 .winner-avatar-wrap { background: #f1f5f9; }
    .rank-2 .winner-icon { color: #64748b; }
    .rank-3 { order: 3; background: linear-gradient(180deg, #fff 60%, #f6fdf9 100%); border-color: #a7f3d0; }
    .rank-3 .medal-badge { background: linear-gradient(135deg, #34d399, #047857); }
    .rank-3 .winner-avatar-wrap { background: #e6f4ea; }
    .rank-3 .winner-icon { color: #047857; }

    .score-badge { font-size: 1rem; border-radius: 12px; background-color: #10b981 !important; color: white; padding: 6px 16px; font-weight: bold; display: inline-block; }

    /* دکمه‌های تم سبز */
    .btn-green-main { background-color: #10b981; color: white; border: none; border-radius: 10px; font-weight: bold; padding: 10px 22px; cursor: pointer; }
    .btn-green-main:hover { background-color: #059669; color: white; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3); }
    .btn-red-main { background-color: #ef4444; color: white; border: none; border-radius: 10px; font-weight: bold; padding: 10px 22px; cursor: pointer; }
    .btn-red-main:hover { background-color: #dc2626; color: white; }

    /* بار فیلتر و جستجو */
    .filter-bar { background: #f8fafc; padding: 18px 25px; border-radius: 14px; margin-bottom: 25px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
    .search-input-group { display: flex; gap: 10px; }
    .search-input-group input[type="number"] { border: 1px solid #cbd5e1; padding: 9px 16px; border-radius: 10px; outline: none; width: 240px; transition: all 0.3s; }
    .search-input-group input[type="number"]:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15); }

    /* باکس راهنمای امتیازدهی بدون پلاس */
    .info-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 15px 20px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; }
    .info-box i { font-size: 1.5rem; color: #10b981; }
    .info-box p { margin: 0; color: #065f46; font-weight: bold; font-size: 0.95rem; line-height: 1.8; }
    .badge-rank { background: #10b981; color: white; padding: 3px 10px; border-radius: 6px; margin: 0 4px; display: inline-block; font-weight: 800; }

    /* جدول سفید و تمیز */
    .custom-table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
    }
    .custom-table thead th {
        background: #f8fafc;
        color: #64748b;
        padding: 18px 10px;
        font-weight: 800;
        font-size: 0.85rem;
        border-bottom: 2px solid #f1f5f9;
    }
    .custom-table tbody td {
        padding: 16px 10px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
        color: #334155;
        vertical-align: middle;
    }
    .custom-table tbody tr:hover { background: #f9fafb; }

    .table-score-span { background: #f0fdf4; color: #10b981; font-weight: bold; padding: 6px 14px; border-radius: 10px; border: 1px solid #bbf7d0; font-size: 0.95rem; }

    /* دکمه‌های داخل جدول */
    .btn-action {
        color: white;
        border-radius: 10px;
        padding: 8px 18px;
        font-weight: 700;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
        text-decoration: none;
    }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); color: white; }

    /* استایل‌های اختصاصی اسلایدشو درون‌صفحه‌ای هوشمند مجموعه‌ها */
    .judged-slider-container { max-width: 100%; background: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02); padding: 25px; border: 1px solid #e2e8f0; direction: rtl; position: relative; }
    .judged-slides-wrapper { position: relative; width: 100%; min-height: 480px; background: #0f172a; border-radius: 18px; padding: 25px; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .judged-slide { width: 100%; text-align: center; display: none; }
    .slide-img-container { width: 100%; height: 380px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
    .slide-img-container img { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 12px; cursor: pointer; box-shadow: 0 8px 25px rgba(0,0,0,0.35); }
    .slider-nav-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(8px); color: white; border: none; width: 48px; height: 48px; border-radius: 50%; cursor: pointer; font-size: 24px; z-index: 10; display: flex; align-items: center; justify-content: center; }
    .slider-nav-btn:hover { background: #10b981; color: white; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); }
    .slider-nav-prev { right: 20px; }
    .slider-nav-next { left: 20px; }
    .photographer-info-panel { background: #f8fafc; border: 1px solid #edf2f7; border-radius: 14px; padding: 15px 25px; margin-top: 15px; width: 100%; max-width: 750px; margin-left: auto; margin-right: auto; }
    .info-toggle-wrapper { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
    .btn-toggle-info { background: linear-gradient(135deg, #4b5563, #374151); color: white; border: none; padding: 8px 18px; border-radius: 10px; font-weight: bold; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; }
    .btn-toggle-info:hover { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
    .hidden-details-content { display: none; margin-top: 15px; padding-top: 15px; border-top: 2px dashed #cbd5e1; }
    .info-badge-item { background: #fff; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 10px; font-size: 0.88rem; font-weight: 600; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.01); }
    .gap-3 { gap: 15px; }

    /* استایل مودال و اسلایدشو تکی قدیمی آثار */
    .modal-content { background-color: #0e0e24!important; box-shadow: rgba(0, 0, 0, 0.5) 0px 20px 40px; border-radius: 16px; border: 1px solid #333; }
    .close span { color: white; font-size: 31px; font-weight: normal; transition: color 0.3s; }
    .close:hover span { color: #ef4444; }
    .modal-title { color: white; font-size: 1.3rem; font-weight: bold; }

    .myprofile { border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); width: 90%; color: white; padding: 15px 20px; border-radius: 12px; margin: 15px auto; display: flex; justify-content: space-around; backdrop-filter: blur(5px); }

    /* صفحه‌بندی */
    .pagination-container { margin-top: 25px; }
    .pagination { justify-content: center; }
    .pagination li a { color: #10b981; border: 1px solid #e2e8f0; padding: 8px 16px; margin: 0 3px; border-radius: 8px; font-weight: bold; transition: all 0.3s; }
    .pagination li:hover a { background-color: #f0fdf4; color: #059669; }
    .pagination li.active a { background-color: #10b981; color: white; border-color: #10b981; }

    /* حالت تمام صفحه */
    .slideshow-container:fullscreen { background-color: #0e0e24; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100vw; height: 100vh; overflow: hidden; }
    .slideshow-container:fullscreen .mySlides img { max-height: 85vh; width: auto; object-fit: contain; }
    .slideshow-container:fullscreen .myprofile { display: none; }

    /* فلش‌های اسلایدشو مودال */
    .prev, .next { cursor: pointer; position: absolute; top: 50%; width: auto; padding: 16px; margin-top: -22px; color: white; font-weight: bold; font-size: 24px; transition: 0.3s ease; border-radius: 0 10px 10px 0; user-select: none; background: rgba(0,0,0,0.3); text-decoration: none; }
    .next { right: 0; border-radius: 10px 0 0 10px; }
    .prev { left: 0; }
    .prev:hover, .next:hover { background-color: rgba(16, 185, 129, 0.8); color: white; text-decoration: none; }

    .count-badge-multi {
        background-color: #d1fae5;
        color: #065f46;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 800;
        font-size: 0.85rem;
    }
    .id-badge {
        color: #64748b;
        background: #f1f5f9;
        padding: 3px 10px;
        border-radius: 6px;
        font-weight: 700;
        font-family: sans-serif;
    }

    @media (max-width: 768px) {
        .podium-container { flex-direction: column; align-items: center; gap: 15px; }
        .winner-card { order: unset !important; transform: none !important; }
        .filter-bar { flex-direction: column; align-items: stretch; }
        .search-input-group { flex-direction: column; }
        .search-input-group input[type="number"] { width: 100%; }
        .info-box { flex-direction: column; text-align: center; }
        .slider-nav-btn { width: 38px; height: 38px; font-size: 18px; }
    }
</style>

<div class="page-wrapper">
    <div class="page-header">
        <h4 class="page-title"><i class="fa-solid fa-images text-success"></i> پنل داوری کاربران جشنواره (مجموعه عکس)</h4>
    </div>

    <div class="card-custom">

        <?php if(!empty($data) && $searchid == ""): ?>
            <h5 class="section-title"><i class="fa-solid fa-images text-success"></i> اسلایدشو بررسی مجموعه‌های داوری شده (حفظ محرمانگی عکاس)</h5>
            <div class="judged-slider-container mb-5">
                <div class="judged-slides-wrapper">
                    <button type="button" class="slider-nav-btn slider-nav-prev" onclick="moveJudgedSlide(-1)">&#10094;</button>
                    <button type="button" class="slider-nav-btn slider-nav-next" onclick="moveJudgedSlide(1)">&#10095;</button>

                    <?php foreach($data as $idx => $row):
                        $rawPath = isset($row["image_url"]) ? $row["image_url"] : '';
                        $currentScore = $row["sum_mark"] ? $row["sum_mark"] : 0;
                    ?>
                        <div class="judged-slide" data-index="<?php echo $idx; ?>" style="<?php echo $idx === 0 ? 'display: block;' : 'display: none;'; ?>">
                            <div class="slide-img-container">
                                <?php if($rawPath): ?>
                                    <img src="../<?php echo $rawPath; ?>" alt="کد عکاس: <?php echo $row["id_user"]; ?>" onclick="window.location.href='davarikol_multiaks.php?type=show&id_user=<?php echo $row['id_user']; ?>'" title="برای نمایش آلبوم کلیک کنید">
                                <?php else: ?>
                                    <div class="text-white d-flex flex-column align-items-center justify-content-center" style="width:230px; height:230px; background:rgba(255,255,255,0.04); border-radius:16px; cursor:pointer; border: 2px dashed rgba(255,255,255,0.1);" onclick="window.location.href='davarikol_multiaks.php?type=show&id_user=<?php echo $row['id_user']; ?>'">
                                        <i class="fa fa-folder-open fa-4x text-success mb-2"></i>
                                        <span class="small">مشاهده تصاویر مجموعه</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="photographer-info-panel">
                                <div class="info-toggle-wrapper">
                                    <div>
                                        <span class="text-muted small font-weight-bold">وضعیت هویت مجموعه:</span>
                                        <span class="badge badge-secondary p-2 mr-1 rounded-lg anonym-status-badge">کاملاً مخفی و محرمانه</span>
                                    </div>

                                    <button type="button" class="btn-toggle-info" onclick="togglePhotographerDetails(this)">
                                        <i class="fa fa-eye toggle-icon"></i>
                                        <span class="btn-text">مشاهده اطلاعات عکاس</span>
                                    </button>
                                </div>

                                <div class="hidden-details-content">
                                    <div class="d-flex flex-wrap justify-content-center text-right gap-3">
                                        <div class="info-badge-item text-dark">
                                            <i class="fa fa-user text-muted ml-1"></i> کد کاربری عکاس:
                                            <span class="text-primary font-weight-bold"><?php echo $row['id_user']; ?></span>
                                        </div>
                                        <div class="info-badge-item text-dark">
                                            <i class="fa fa-check-circle text-success ml-1"></i> مجموع امتیاز مجموعه:
                                            <span class="text-success font-weight-bold"><?php echo $currentScore; ?> امتیاز</span>
                                        </div>
                                        <div class="info-badge-item text-dark">
                                            <i class="fa fa-trophy text-warning ml-1"></i> مجموع رتبه داوران:
                                            <span class="text-warning font-weight-bold"><?php echo $row['sum_rank']; ?></span>
                                        </div>
                                        <div class="info-badge-item text-dark p-1" style="border:none; background:transparent;">
                                            <a href="davarikol_multiaks.php?type=show&id_user=<?php echo $row['id_user']; ?>" class="btn-action btn-excel btn-sm font-weight-bold px-3"><i class="fa fa-images ml-1"></i> باز کردن آلبوم تصاویر</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if(!empty($top3) && $searchid == ""): ?>
            <h5 class="section-title"><i class="fa-solid fa-crown text-success"></i> مجموعه‌های برتر جشنواره (مقام اول تا سوم)</h5>
            <div class="podium-container">
                <?php foreach($top3 as $index => $row):
                    $rank = $index + 1;
                    $currentScore = $row["sum_mark"] ? $row["sum_mark"] : 0;
                    $badgeIcon = ($rank == 1) ? 'fa-crown' : 'fa-medal';
                ?>
                    <div class="winner-card rank-<?php echo $rank; ?>">
                        <div class="medal-badge">
                            <i class="fa-solid <?php echo $badgeIcon; ?>"></i>
                        </div>
                        <div class="winner-avatar-wrap">
                            <i class="fa-solid fa-user-tie winner-icon"></i>
                        </div>
                        <h6 class="font-weight-bold text-dark mb-1">مقام <?php echo ($rank == 1 ? 'اول' : ($rank == 2 ? 'دوم' : 'سوم')); ?></h6>
                        <div class="text-muted small mb-2">کد عکاس: <?php echo $row["id_user"]; ?></div>
                        <div class="score-badge">
                            امتیاز: <?php echo $currentScore; ?>
                        </div>
                        <div class="text-muted small mt-2">مجموع رتبه: <?php echo $row["sum_rank"]; ?></div>
                        <div class="mt-3">
                            <a class="btn-action btn-excel btn-sm px-3" href="davarikol_multiaks.php?type=show&id_user=<?php echo $row["id_user"]; ?>"><i class="fa fa-eye"></i> مشاهده</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="filter-bar">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label-small m-0">تعداد نمایش:</label>
                <select class="form-control-custom d-inline-block" name="state" id="maxRows" style="width: 85px;">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                </select>
            </div>

            <form action="davarikol_multiaks.php" method="post" class="m-0 search-input-group">
                <input type="number" class="form-control-custom" name="idsearch" required placeholder="کد عکاس را وارد کنید...">
                <button type="submit" name="search_user" class="btn-action btn-search">جستجو</button>
            </form>
        </div>

        <?php
        if(isset($_POST['search_user']) && $searchid != "") {
            echo "<button class='btn-red-main mb-4' onclick=\"window.location.href='davarikol_multiaks.php';\"><i class='fa fa-times ml-1'></i> لغو جستجو و نمایش تمام سطرها</button>";
        }
        ?>

        <div class="info-box">
            <i class="fa-solid fa-circle-info"></i>
            <p>
                <strong>راهنمای رتبه‌بندی گروه‌ها:</strong><br>
                گروه <span class="badge-rank">A</span> (50 - 53) &nbsp;///&nbsp;
                گروه <span class="badge-rank">B</span> (40 - 43) &nbsp;///&nbsp;
                گروه <span class="badge-rank">C</span> (30 - 33) &nbsp;///&nbsp;
                گروه <span class="badge-rank">D</span> (20 - 23)
            </p>
        </div>

        <h5 class="section-title"><i class="fa-solid fa-table text-success"></i> جدول کامل امتیازات آثار مجموعه‌ای</h5>
        <div class="table-wrapper">
            <table id="tableData" class="custom-table">
                <thead>
                    <tr>
                        <th>کد عکاس</th>
                        <th>کد عکس</th>
                        <th>مجموعه نمره</th>
                        <th>مجموعه رتبه</th>
                        <th>عملیات نمایش</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // اگر سرچ انجام شده باشد کل دیتا نمایش داده می‌شود، در غیر این صورت مابقی آثار (بعد از رتبه 3) در جدول لیست می‌شوند
                $listData = ($searchid != "") ? $data : $others;

                if(!empty($listData)) {
                    foreach($listData as $row) {
                        echo '<tr>
                                <td><span class="id-badge">'.$row["id_user"].'</span></td>
                                <td><span class="count-badge-multi">'.$row["id_picture"].'</span></td>
                                <td><span class="table-score-span">'.$row["sum_mark"].'</span></td>
                                <td><span class="text-muted font-weight-bold">'.$row["sum_rank"].'</span></td>
                                <td>
                                    <a class="btn-action btn-excel" href="davarikol_multiaks.php?type=show&id_user='.$row["id_user"].'"><i class="fa fa-eye ml-1"></i> مشاهده عکس</a>
                                    <a class="btn-action btn-pdf" href="davarikol_multiaks.php?type=show&id_user='.$row["id_user"].'&mode=fs" style="margin-right: 5px;"><i class="fa fa-expand ml-1"></i> تمام‌صفحه</a>
                                </td>
                            </tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" class="text-center py-4 text-muted">رکوردی جهت نمایش یافت نشد.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>

        <div class='pagination-container'>
            <nav><ul class="pagination" id="pagination"></ul></nav>
        </div>

    </div>
    </div>
    </div>
</div>

<div class="modal fade" id="slider_multiaks">
    <div class="modal-xl modal-dialog-centered modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <h4 class="modal-title"><i class="fa fa-images ml-2 text-success"></i> نمایش تصاویر مجموعه</h4>
                <div>
                    <button type="button" class="btn btn-sm btn-green-main px-3" onclick="toggleFullScreen()" style="margin-left:10px;"><i class="fa fa-expand ml-1"></i> تمام‌صفحه (F)</button>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="outline: none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body p-0 pb-4">
                <div class="slideshow-container position-relative" id="fs_container">
                    <?php
                    if($users > 0) {
                        $mysql = "SELECT * FROM information_user INNER JOIN upload_image_address ON information_user.id=upload_image_address.id_user WHERE id_user='$users'";
                        $myresult = mysqli_query($conn, $mysql);
                        while($myrow = mysqli_fetch_assoc($myresult)) {
                            echo '
                            <div class="mySlides text-center" style="display:none;">
                                <div class="myprofile">
                                    <div><i class="fa fa-user text-success ml-1"></i> نام: <b>'.$myrow["name_family"].'</b></div>
                                    <div><i class="fa fa-phone text-success ml-1"></i> تماس: <b>'.$myrow["phone"].'</b></div>
                                </div>
                                <img style="max-width:90%; object-fit:contain; max-height:65vh; border-radius:12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);" src="../'.$myrow["image_url"].'">
                            </div>';
                        }
                    }
                    ?>
                    <a class="prev" onclick="plusSlides(-1)">&#10095;</a>
                    <a class="next" onclick="plusSlides(1)">&#10094;</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/jquery.min.js"></script>
<script src="../js/jquery-ui.min.js"></script>
<script src="../js/paging.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/panel.js"></script>

<script>
let slideIndex = 1;
showSlides(slideIndex);

function plusSlides(n) { showSlides(slideIndex += n); }
function currentSlide(n) { showSlides(slideIndex = n); }

function showSlides(n) {
    let i;
    let slides = document.getElementsByClassName("mySlides");
    if (slides.length === 0) return;
    if (n > slides.length) {slideIndex = 1}
    if (n < 1) {slideIndex = slides.length}
    for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }
    if(slides[slideIndex-1]) {
        slides[slideIndex-1].style.display = "block";
    }
}

function toggleFullScreen() {
    let elem = document.getElementById("fs_container");
    if (!document.fullscreenElement) {
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

// منطق اسلایدشو هوشمند درون‌صفحه‌ای جدید
let currentSliderIdx = 0;

function moveJudgedSlide(direction) {
    const slides = $(".judged-slide");
    if(slides.length === 0) return;

    // مخفی‌سازی اسلاید جاری و ریست کردن اطلاعات محرمانه آن قبل از جابجایی
    const prevSlide = $(slides[currentSliderIdx]);
    prevSlide.hide();
    prevSlide.find(".hidden-details-content").hide();

    const btn = prevSlide.find(".btn-toggle-info");
    btn.find(".toggle-icon").removeClass("fa-eye-slash").addClass("fa-eye");
    btn.find(".btn-text").text("مشاهده اطلاعات عکاس");
    btn.css("background", "");
    prevSlide.find(".anonym-status-badge").removeClass("badge-success").addClass("badge-secondary").text("کاملاً مخفی و محرمانه");

    // محاسبه مکان اسلاید بعدی
    currentSliderIdx += direction;
    if(currentSliderIdx >= slides.length) currentSliderIdx = 0;
    if(currentSliderIdx < 0) currentSliderIdx = slides.length - 1;

    $(slides[currentSliderIdx]).fadeIn(300);
}

function togglePhotographerDetails(buttonElement) {
    const btn = $(buttonElement);
    const slide = btn.closest(".judged-slide");
    const detailsPanel = slide.find(".hidden-details-content");
    const icon = btn.find(".toggle-icon");
    const textSpan = btn.find(".btn-text");
    const statusBadge = slide.find(".anonym-status-badge");

    detailsPanel.slideToggle(250, function() {
        if (detailsPanel.is(":visible")) {
            icon.removeClass("fa-eye").addClass("fa-eye-slash");
            textSpan.text("مخفی‌سازی اطلاعات عکاس");
            btn.css("background", "linear-gradient(135deg, #ef4444, #dc2626)");
            statusBadge.removeClass("badge-secondary").addClass("badge-success").text("اطلاعات نمایان شد");
        } else {
            icon.removeClass("fa-eye-slash").addClass("fa-eye");
            textSpan.text("مشاهده اطلاعات عکاس");
            btn.css("background", "");
            statusBadge.removeClass("badge-success").addClass("badge-secondary").text("کاملاً مخفی و محرمانه");
        }
    });
}

$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('mode') === 'fs') {
        $('#slider_multiaks').on('shown.bs.modal', function () {
            setTimeout(toggleFullScreen, 500);
        });
    }

    $('#pagination').on('click', 'li', function () {
        $('#pagination li').removeClass('active');
        $(this).addClass('active');
    });
});

document.addEventListener('keydown', function(event) {
    if($('#slider_multiaks').hasClass('show')) {
        if (event.key === "ArrowLeft") plusSlides(1);
        else if (event.key === "ArrowRight") plusSlides(-1);
        else if (event.key === "f" || event.key === "F") toggleFullScreen();
    }
});
</script>

<?php include "footer.php"; ?>
