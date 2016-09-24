<?php
include("model/inc/config.php");

$action = !empty($_GET['action']) ? $_GET['action'] : "";
$primkey = $_GET['primkey'];
$bizId = substr($primkey, 4);
$query_primaryBiz = $conn->prepare("SELECT * FROM `ctg_bussinesses` WHERE `bid` = :bid OR `primkey` = :BizPrimary");
$query_primaryBiz->bindValue(':bid', $bizId);
$query_primaryBiz->bindValue(':BizPrimary', $primkey);
$query_primaryBiz->execute();
if ($query_primaryBiz->rowCount()) {
    $biz = $query_primaryBiz->fetchObject();
} else {
    safe_redirect($siteurl . "/404");
}

if (rand(1, 10) == 4) $visitplus = 3;
else $visitplus = 1;
$query_newVisit = $conn->prepare("UPDATE `ctg_bussinesses` SET visits = visits+ $visitplus WHERE bid = :bid LIMIT 1");
$query_newVisit->bindValue(':bid', $biz->bid);
$query_newVisit->execute();

$ctname = locdata($biz->city)->local_name;

if (strpos($biz->timeline, ':') !== false) {
    $timeline = explode(":", $user->timeline);

    $ReserveActivePerm = $timeline['0'];
    $BizReserveShow = $timeline['1'];
    $BizDailyProgramShow = $timeline['2'];
    $BizReserveShow = $timeline['3'];
    $BizFollowShow = $timeline['4'];
    $BizLikeShow = $timeline['5'];
} else {
    $ReserveActivePerm = 1;
    $BizReserveShow = 0;
    $BizDailyProgramShow = 1;
    $BizReserveShow = 0;
    $BizFollowShow = 1;
    $BizLikeShow = 1;
}


if ($action == "addPic") {
  $canonical = '<link rel="canonical" href="http://www.citygram.ir/@'.$primkey.'/photos/افزودن-عکس-'.str_replace(" ", "-", $biz->name).'" />';

    $ptitle = "افزودن عکس به " . $biz->name . " - " . $ctname . " - سیتی گرام";
    $pdes = "در " . $ptitle . " می توانید به صفحه کسب و کار مورد نظر خود عکس اضافه کنید .";
    $pkeywords = $biz->name . ",تصاویر " . $biz->name . ",گالری " . $biz->name . ", گالری عکس " . $biz->name . ",gallery " . $biz->name . ",عکس های " . $biz->name;
    include_once("template/header.php");


    if ($logged) {
        // USER FAV CATS
        $query_CheckPreviousFav = $conn->prepare('SELECT * FROM `ctg_usersFavCats` WHERE `uid`= :uid AND `cid` = :cid ');
        $query_CheckPreviousFav->bindValue(':uid', $user->user_id);
        $query_CheckPreviousFav->bindValue(':cid', $biz->subcat);
        $query_CheckPreviousFav->execute();

        if ($query_CheckPreviousFav->rowCount() == 0) {
            $query_AddFavCat = $conn->prepare('INSERT INTO `ctg_usersFavCats` VALUES (:uid,:cid,1) ');
            $query_AddFavCat->bindValue(':uid', $user->user_id);
            $query_AddFavCat->bindValue(':cid', $biz->subcat);
            $query_AddFavCat->execute();
        } else {
            $query_UpdateFavCat = $conn->prepare('UPDATE `ctg_usersFavCats` SET visits = visits+1 WHERE `uid`= :uid AND `cid` = :cid ');
            $query_UpdateFavCat->bindValue(':uid', $user->user_id);
            $query_UpdateFavCat->bindValue(':cid', $biz->subcat);
            $query_UpdateFavCat->execute();
        }
    } else {
        safe_redirect($siteurl . "/page/auth/loginAndRegister/");
    }

    $thispageScript = '
	<script>
		$("#p_upload_button").on("click", function() {
			if(index > 0){
				$("#uploadimage").html("<div id=\"reload_layer\"></div>"+
				"<div id=\"image_preview\"><img id=\"previewing\" src=\"http://citygramcdn.ir/bphoto/' . urlencode(imageEncrypt("usrUpload/text2.png:' ':::1")) . '/220.ctg\" /></div>"+
				"<hr id=\"line\">"+
				"<div id=\"selectImage\">"+
					"<input type=\"hidden\" value=\"' . encryptIt("uploadFile_" . random_string("5")) . '\" name=\"key\">"+
					"<input type=\"hidden\" value=\""+ctcode+"\" name=\"citycode\">"+
					"<input type=\"hidden\" name=\"user\" value=\"' . encryptIt($user->user_id) . '\" />"+
					"<input type=\"hidden\" name=\"kind\" value=\"' . encryptIt("bizphoto_" . random_string("5")) . '\" />"+
					"<label>تصویر خودتون رو انتخاب کنید : </label>"+
					"<input type=\"file\" name=\"file\" class=\"file_Upload\" required />"+
					"<button type=\"submit\" class=\"btn btn-primary sbtn\" name=\"submitBtn\">شروع آپلود</button>"+
				"</div>");
			}
		});
		
		var index = 0; 
		var letUpload = 1;
		$("#uploadimage").on("submit", (function(e) {
			if($("input[name=citycode]").val() == ""){alert("ابتدا شهر محل کسب و کار را انتخاب کنید");return false;}
			e.preventDefault();
			
			if(letUpload){
				$("#reload_layer").prepend("<i style=\"font-size:42px;color:#555\" class=\"fa fa-spinner fa-spin\"></i>");
				$("#image_preview").css({
					"height": $("#image_preview").height()+"px",
					"width": $("#image_preview").width()+"px",
					"top": $("#image_preview").offset().top,
				}).fadeTo(0, 0.1);
				letUpload = 0;
				$.ajax({
					url: "http://citygramcdn.ir/upload.php", 	// Url to which the request is send
					type: "POST", 						// Type of request to be send, called as method
					data: new FormData(this), 			// Data sent to server, a set of key/value pairs (i.e. form fields and values)
					contentType: false, 				// The content type used when sending data to the server.
					cache: false, 						// To unable request pages to be cached
					processData: false,					// To send DOMDocument or non processed data file it is set to false
					success: function(data){			// A function to be called if request succeeds{
						var address = data.match("<code style=\'display:none;\'>(.*)</code>");
						var key = data.match("<key style=\'display:none;\'>(.*)</key>");console.log(address[1] );
						$("#uploadimage").html("<img src=\"http://citygramcdn.ir/" + address[1] + "\" title=\"pic"+index+"\" style=\"padding:0 !important;border:1px solid #ccc;border-radius:4px;height:100px !important;margin-bottom:15px;width:auto;\" class=\"uppic\" /><br/>"+
							data+
							"<br/><span style=\"color:#c50\"><i class=\"fa fa-cog fa-spin ml5\"></i> در حال انتقال کمی صبر کنید ...</span>");
						var s = document.createElement("script");
						s.type = "text/javascript";
						s.text  = "setTimeout(function() { window.location.href = siteurl + \"/@' . $primkey . '/photos/"+encodeURI(key[1])+"\";} , \"2000\" );"  
						$("head").append(s);
					}
				});
			}
		}));

		
		// Function to preview image after validation
		$(document).on("change", ".file_Upload" , (function() {
			$("#reload_layer").empty();
			var file = this.files[0];
			var imagefile = file.type;
			var match = ["image/jpeg", "image/png", "image/jpg"];
			if (!((imagefile == match[0]) || (imagefile == match[1]) || (imagefile == match[2]))) {
				$("#previewing").attr("src", "http://citygramcdn.ir/bphoto/' . urlencode(imageEncrypt("usrUpload/text2.png:' ':::1")) . '/200.ctg");
				$("#reload_layer").html("<p id=\"error\">لطفا فایل با پسوند مجاز انتخاب کنید !</p><span id=\"error_message\">فایل های فرمت jpeg, jpg و png مجاز به آپلود هستند .</span>");
				letUpload = 0;
				return false;
			} else if( this.files.length && this.files[ 0 ].size > 3000 * 1024 ) {
				$("#previewing").attr("src", "http://citygramcdn.ir/bphoto/' . urlencode(imageEncrypt("usrUpload/text2.png:' ':::1")) . '/200.ctg");
				$("#reload_layer").html("<p id=\"error\">حجم فایل انتخابی بیشتر از حجم مجاز است [" + format_size(this.files[ 0 ].size ) + "]</p><span id=\"error_message\">حداکثر حجم مجاز برای آواتار اعضا 3 مگابایت است .</span>");
				letUpload = 0;
				return false;
			} else {
				var reader = new FileReader();
				reader.onload = function(e) {
					img = new Image();
					img.src = reader.result;
					img.onload = function() {
						if(this.width < 300 && this.height < 300) { 
							$("#previewing").attr("src", "http://citygramcdn.ir/bphoto/' . urlencode(imageEncrypt("usrUpload/text2.png:' ':::1")) . '/220.ctg");
							$("#reload_layer").html("<p id=\"error\">طول و عرض فایل بسیار کوچک است . [" + this.width + "px / " + this.height + " px]</p><span id=\"error_message\">نباید کمتر از 300 پیکسل باشد .</span>");
							letUpload = 0;
							return false;
						}
					};
					$("#file").css("color", "green");
					$("#image_preview").css("display", "block");
					$("#previewing").attr("src", e.target.result);
					$("#previewing").attr("width", "auto");
					$("#previewing").attr("height", "220px");
				};
				reader.readAsDataURL(this.files[0]);
				letUpload = 1;
			}
		}));
		function format_size(size) {
			var units = ("B KB MB GB TB PB").split(" ");
			var mod = 1024;
			var i = 0;
			for ( i = 0 ; size > mod; i++) {
				size /= mod;
			}
			size = size.toString();
			var endIndex = size.indexOf(".")+3;
			return size.substr(0, endIndex)+" "+units[i];
		}</script>'; ?>
    <div role="main" class="main">

    <section class="page-top basic">
        <div class="page-top-info container" style="line-height:auto">
            <div class="row">
                <div class="col-md-4 col-xs-12 col-sm-3 pull-left" style="text-align:left;">
                    <a href="<?= $siteurl; ?>/@<?= $primkey; ?>/photos/گالری_عکس_<?= str_replace(" ", "-", $biz->name); ?>"
                       class="btn3d btn-danger btn-md pb5"><i class="fa fa-photo ml10"></i> تصاویر</a>
                </div>
                <div class="col-md-8 col-xs-12" style="text-align:right;">
                    <a href="<?= $siteurl; ?>/@<?= $primkey; ?>" title="<?= $biz->name; ?>"><h3
                            class="white biz-title"><?= $biz->name; ?></h3></a>
                    <?
                    if ($biz->review_count !== "0") {
                        $stars = $biz->review_sum / $biz->review_count;
                        $haveHalf = is_float($stars) ? "half" : "";
                    } else {
                        $stars = 0;
                        $haveHalf = "";
                    }
                    $ctname = locdata($biz->city)->local_name;
                    ?>
                    <span class="rating-input gold mr10 lgsize"
                          data-stars="<?= ceil($stars); ?>-5<?= $haveHalf; ?>"></span> <?= $biz->review_count; ?> <span
                        class="small">رای (مجموع : <?= $biz->review_sum == "" ? "0" : $biz->review_sum; ?>)</span><br/>
                    <i class="fa fa-map-marker ml10"></i><a href="<?= $siteurl; ?>/search/city=<?= $ctname; ?>/"
                                                            class="white"><?= $ctname; ?></a> - <a
                        href="<?= $siteurl; ?>/search/city=<?= $ctname; ?>-subct=<?= $biz->subcity; ?>"
                        class="white"><?= citydata($biz->subcity)->name; ?></a>
                </div>
            </div>
            <br/>
        </div>
    </section>
    <br class="clear"/>

    <div class="container mb15">
        <div class="row">
            <div class="col-md-6">
                <h4 style="font-size:16px">آپلود عکس در <?= $biz->name; ?></h4>
                <img src="http://s5.citygramcdn.ir/media/grpMwVNgWbeiZ05YnaPT/pic150.png" width="150px"
                     class="img-responsive pull-left mr20" alt="photoUplaod"/>
						<span style="text-align:justify">
							<i class="fa fa-check ml5 green"></i> لطفا فقط عکس های مربوطه به کسب و کار را آپلود کنید .<br/>
							<i class="fa fa-check ml5 green"></i> عکس های با مضامین زشت یا مغایر با قوانین جمهوری اسلامی ایران و همچنین قوانین سایت سیتی گرام حذف شده و شخص خاطی به پلیس فتا تحویل داده میشود .<br/>
							<i class="fa fa-check ml5 green"></i> عکس های آپلودی می بایست از حجم و سایز مناسب برخوردار باشند .
						</span>
            </div>
            <div class="col-md-6">
                <form id="uploadimage" action="" method="post" enctype="multipart/form-data">
                    <div id="reload_layer"></div>
                    <div id="image_preview" style="background:#fff !important;text-align:center">
                        <img id="previewing"
                             src="http://citygramcdn.ir/bphoto/<?= urlencode(imageEncrypt('usrUpload/text2.png:" ":::1')); ?>/220.ctg"/>
                    </div>
                    <hr id="line">
                    <div id="selectImage">
                        <input type="hidden" value="<?= encryptIt("uploadFile_" . random_string('5')); ?>" name="key">
                        <input type="hidden" name="biz" value="<? echo encryptIt($biz->bid); ?>"/>
                        <input type="hidden" name="user" value="<? echo encryptIt($user->user_id); ?>"/>
                        <input type="hidden" name="kind"
                               value="<? echo encryptIt("userUpload_" . random_string('6')); ?>"/>
                        <label>تصویر خودتون رو انتخاب کنید : </label>
                        <input type="file" name="file" class="file_Upload" required/>
                        <button type="submit" class="btn btn-primary sbtn" name="submitBtn">شروع آپلود</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <br/>

<? } else if ($action == "photos") {
$canonical = '<link rel="canonical" href="http://www.citygram.ir/@'.$primkey.'/photos/گالری-عکس-'.str_replace(" ", "-", $biz->name).'" />';

    $ptitle = "تصاویر " . $biz->name . " - " . $ctname . " - سیتی گرام";
    $pdes = "در " . $ptitle . " می توانید به صفحه کسب و کار مورد نظر خود عکس اضافه کنید .";
    $pkeywords = $biz->name . ",تصاویر " . $biz->name . ",گالری " . $biz->name . ", گالری عکس " . $biz->name . ",gallery " . $biz->name . ",عکس های " . $biz->name;
    include_once("template/header.php");


    if ($logged) {
        // USER FAV CATS
        $query_CheckPreviousFav = $conn->prepare('SELECT * FROM `ctg_usersFavCats` WHERE `uid`= :uid AND `cid` = :cid ');
        $query_CheckPreviousFav->bindValue(':uid', $user->user_id);
        $query_CheckPreviousFav->bindValue(':cid', $biz->subcat);
        $query_CheckPreviousFav->execute();

        if ($query_CheckPreviousFav->rowCount() == 0) {
            $query_AddFavCat = $conn->prepare('INSERT INTO `ctg_usersFavCats` VALUES (:uid,:cid,1) ');
            $query_AddFavCat->bindValue(':uid', $user->user_id);
            $query_AddFavCat->bindValue(':cid', $biz->subcat);
            $query_AddFavCat->execute();
        } else {
            $query_UpdateFavCat = $conn->prepare('UPDATE `ctg_usersFavCats` SET visits = visits+1 WHERE `uid`= :uid AND `cid` = :cid ');
            $query_UpdateFavCat->bindValue(':uid', $user->user_id);
            $query_UpdateFavCat->bindValue(':cid', $biz->subcat);
            $query_UpdateFavCat->execute();
        }
    }

    $file = !empty($_REQUEST['pic']) ? base64_decode($_REQUEST['pic']) : "";
    $addPicMessage = "";
    $err = 0;
    function checkExternalFile($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $retCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $retCode;
    }

    $fileExists = checkExternalFile("http://citygramcdn.ir/" . urldecode($file));
    if (strlen($file) > 0) {
        if ($fileExists == 200) {
            $addPic_check = $conn->prepare("SELECT * FROM `ctg_photos` WHERE address = :address ");
            $addPic_check->bindValue(':address', $file);
            $addPic_check->execute();
            if ($addPic_check->rowCount() == 0) {
                $addPic = $conn->prepare("INSERT INTO `ctg_photos`(`byuid`, `bid`, `address`, `date`) VALUES (:uid,:bid,:address,:date) ");
                $addPic->bindValue(':uid', $user->user_id);
                $addPic->bindValue(':bid', $biz->bid);
                $addPic->bindValue(':address', $file);
                $addPic->bindValue(':date', $now);
                if ($addPic->execute()) {
                    $addPicMessage = "<i class='fa fa-check green ml10'></i> تصویر با موفقیت درج شد .";
                }
            } else {
                $addPicMessage = "<i class='fa fa-times red  ml10'></i>  تصویر قبلا افزوده شده است !";
            }
        } else {
            $addPicMessage = "<i class='fa fa-times red ml10'></i>  خطا در افزودن تصویر !";
        }
    }

    $page = !empty($_GET['page']) ? $_GET['page'] : 1;

    $ftspp = 16;
    $start = ($page - 1) * $ftspp;
    $query_counter = $conn->prepare("SELECT * FROM `ctg_photos` WHERE `bid` = :bid ");
    $query_counter->bindValue(':bid', $biz->bid);
    $query_counter->execute();
    $count = $query_counter->rowCount();
    $firstpage = $pervpage = $paged_others = $nextpage = $lastpage = $pageinfo = "";

    if ($count - $ftspp > 0) {
        $preLink = $siteurl . "/@" . $primkey . "/photos/page/";
        $siffixLink = "";

        $paged_total = ceil($count / $ftspp);
        $paged_last = $paged_total;    //صفحه آخر
        $paged_middle = $page + 3;    //صفحه میانی
        $paged_start = $paged_middle - 3;    //شروع صفحه بندی

        if ($page > 1) {
            $firstpage = '<a class="animate ftactive" data-toggle="tooltip"  data-placement="top"  href="' . $preLink . '1' . $siffixLink . '" title="صفحه نخست" ><i class="fa fa-angle-double-right"></i> </a>';
        } else {
            $firstpage = '<a class="ftdeactive" data-toggle="tooltip"  data-placement="top"  title="صفحه نخست" ><i class="fa fa-angle-double-right"></i> </a>';
        }

        if ($page <= $paged_last - 1) {
            $lastpage = '<a class="animate ftactive" data-toggle="tooltip"  data-placement="top"  href="' . $preLink . $paged_last . $siffixLink . '" title="صفحه آخر" ><i class="fa fa-angle-double-left"></i> </a>';
        } else {
            $lastpage = '<a class="ftdeactive" data-toggle="tooltip"  data-placement="top"  title="صفحه آخر" ><i class="fa fa-angle-double-left"></i> </a>';
        }
        if ($page > 1) {
            $paged_perv = $page - 1;
            $pervpage = '<a class="animate ftactive" data-toggle="tooltip"  data-placement="top"  href="' . $preLink . $paged_perv . $siffixLink . '" title="صفحه قبلی"><i class="fa fa-angle-right"></i> </a>';
        } else {
            $pervpage = '<a class="ftdeactive" data-toggle="tooltip"  data-placement="top"  title="صفحه قبلی" ><i class="fa fa-angle-right"></i> </a>';
        }
        if ($page <= $paged_last - 1) {
            $paged_next = $page + 1;
            $nextpage = '<a class="animate ftactive" data-toggle="tooltip"  data-placement="top"  style="margin-right:-4px;" href="' . $preLink . $paged_next . '" title="صفحه بعدی"><i class="fa fa-angle-left"></i> </a>';
        } else {
            $nextpage = '<a class="ftdeactive" data-toggle="tooltip"  data-placement="top"  style="margin-right:-4px;" title="صفحه بعدی"><i class="fa fa-angle-left"></i> </a>';
        }
        $paged_others = "";
        for ($i = $paged_start - 2; $i <= $paged_middle; $i++) {
            if ($i > 0 && $i <= $paged_last) {
                if ($i == $page) {
                    $paged_others .= '<a class="ftdeactive" style="background:#f8f8f8;" data-toggle="tooltip"  title="صفحه فعلی" data-placement="top" >صفحه' . $i . '</a>';
                } else {
                    $paged_others .= '<a class="animate ftactive" data-toggle="tooltip"  data-placement="top"  href="' . $preLink . $i . $siffixLink . '" title="صفحه ' . $i . '">صفحه' . $i . '</a>';
                }
            }
        }
        $pageinfo = '<div class="paged-link-info">&raquo; صفحه: ' . $page . ' از ' . $paged_total . '</div>';
    }

    $query_pics = $conn->prepare("SELECT * FROM `ctg_photos` WHERE `bid` = :bid LIMIT $start, $ftspp  ");
    $query_pics->bindValue('bid', $biz->bid);
    $query_pics->execute();
    $pics = $query_pics->fetchAll();


    $thispageScript = "
		<script type='text/javascript'>
		  jQuery(document).ready(function($) {
				$('#myCarousel').carousel({
						interval: 5000
				});
		 
				//Handles the carousel thumbnails
				$('[id^=carousel-selector-]').click(function () {
				var id_selector = $(this).attr('id');
				try {
					var id = /-(\d+)$/.exec(id_selector)[1];
					jQuery('#myCarousel').carousel(parseInt(id));
				} catch (e) {
					console.log('Regex failed!', e);
				}
			});
				// When the carousel slides, auto update the text
				$('#myCarousel').on('slid.bs.carousel', function (e) {
						 var id = $('.item.active').data('slide-number');
						$('#carousel-text').html($('#slide-content-'+id).html());
				});
		});
		</script>";
    ?>
    <div role="main" class="main">
    <section class="page-top basic">
        <div class="page-top-info container" style="line-height:auto">
            <div class="row">
                <div class="col-md-4 col-xs-12 col-sm-3 pull-left" style="text-align:left;">
                    <a href="<?= $siteurl; ?>/@<?= $primkey; ?>/addPic" class="btn3d btn-danger btn-md pb5"><i
                            class="fa fa-photo ml10"></i> افزودن عکس</a>
                </div>
                <div class="col-md-8 col-xs-12 profTitle" style="text-align:right;">
                    <a href="<?= $siteurl; ?>/@<?= $primkey; ?>" class="white"><h3
                            class="white biz-title"><?= $biz->name; ?></h3></a>
                    <?
                    if ($biz->review_count !== "0") {
                        $stars = $biz->review_sum / $biz->review_count;
                        $haveHalf = is_float($stars) ? "half" : "";
                    } else {
                        $stars = 0;
                        $haveHalf = "";
                    }

                    ?>
                    <span class="rating-input gold mr10 lgsize"
                          data-stars="<?= ceil($stars); ?>-5<?= $haveHalf; ?>"></span> <?= $biz->review_count; ?> <span
                        class="small">رای (مجموع : <?= $biz->review_sum == "" ? "0" : $biz->review_sum; ?>)</span><br/>
                    <i class="fa fa-map-marker ml10"></i><a href="<?= $siteurl; ?>/search/city=<?= $ctname; ?>/"
                                                            class="white"><?= $ctname; ?></a> - <a
                        href="<?= $siteurl; ?>/search/city=<?= $ctname; ?>-subct=<?= $biz->subcity; ?>"
                        class="white"><?= citydata($biz->subcity)->name; ?></a>
                </div>
            </div>
            <br/>
        </div>
    </section>
    <br class="clear"/>
    <div class="container mb15">
        <style type="text/css">
            .hide-bullets {
                list-style: none;
                padding-right: 0
            }

            .hide-bullets li {
                float: right;
                padding-right: 0
            }

            .thumbnail {
                padding: 0
            }

            .carousel-inner > .item > img, .carousel-inner > .item > a > img {
                width: 100%
            }
        </style>
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <span class="pull-left">نمایش تصاویر <?= (++$start); ?>
                    تا <?= $count < $ftspp ? $count : $ftspp * $page; ?> از مجموع <?= $count; ?> </span>
                <? if (strlen($addPicMessage) > 0) echo $addPicMessage; ?>
                <? if ($logged) if ($biz->owner == $user->user_id) echo '<a href="' . $siteurl . '/BusinessCP/photos-' . $primkey . '" title="مدیریت تصاویر" >[ <i class="fa fa-pencil ml5"></i> <span class="small">مدیریت تصاویر</span> ]</a>'; ?>
                <hr/>
                <!-- Slider -->
                <div class="row" style="text-align:center !important">
                    <div class="col-sm-6 pull-right" id="slider-thumbs" style="text-align:center !important">
                        <!-- Bottom switcher of slider -->
                        <ul class="hide-bullets">

                            <?
                            $picTumbId = 0;
                            $slidesPics = "";
                            foreach ($pics as $pic) {
                                ?>

                                <li class="col-sm-3">
                                    <a class="thumbnail" id="carousel-selector-<?= $picTumbId; ?>">
                                        <img alt="<?= $biz->name; ?>"
                                             src="http://citygramcdn.ir/bphoto/<?= urlencode(imageEncrypt($pic['address'] . ': " سيتي گرام " :1: 10: :1')); ?>/122.ctg"
                                             style="width:122px;height:122px">
                                    </a>
                                </li>

                                <?
                                $tumbImage = urlencode(imageEncrypt($pic['address'] . ': سيتي گرام -  @'.$primkey.' : 0: 17: :1'));
                                if ($picTumbId == 0) $act = 'active';
                                else $act = '';
                                $slidesPics .= '<div class="' . $act . ' item" data-slide-number="' . $picTumbId . '">
													<img alt="' . $biz->name . '" src="http://citygramcdn.ir/bphoto/'. $tumbImage .'/567.ctg" style="width:567px;height:567px" />
												</div>';
                                $picTumbId++;
                            } ?>

                        </ul>
                    </div>
                    <div class="col-sm-6 col-xs-12">
                        <!-- Top part of the slider -->
                        <div class="row">
                            <div class="col-sm-12" id="carousel-bounding-box">
                                <div class="carousel slide" id="myCarousel">
                                    <!-- Carousel items -->
                                    <div class="carousel-inner">

                                        <?= $slidesPics; ?>

                                    </div>
                                    <a class="left carousel-control" style="left:15px !important" href="#myCarousel"
                                       role="button" data-slide="prev">
                                        <span class="fa fa-arrow-circle-left gray"
                                              style="font-size:40px !important"></span>
                                    </a>
                                    <a class="right carousel-control" style="right:8px !important" href="#myCarousel"
                                       role="button" data-slide="next">
                                        <span class="fa fa-arrow-circle-right gray"
                                              style="font-size:40px !important"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--/Slider-->
                </div>
                <hr/>
                <span class="pull-right mb10"><div
                        class="ftpagenav"><?= $firstpage . ' ' . $pervpage . ' ' . $paged_others . ' ' . $nextpage . ' ' . $lastpage; ?></div></span>
            </div>
        </div>
    </div>
    <br/>
<? } else if ($action == "navigate") {
$canonical = '<link rel="canonical" href="http://www.citygram.ir/@'.$primkey.'/navigate/راهبری-به-'.str_replace(" ", "-", $biz->name).'" />';

    $ptitle = "راهبری به " . $biz->name . " - " . $ctname . " - سیتی گرام";
    $pdes = "از هر موقعیت جغرافیایی یا مکان فعلی شما با GPS به " . $biz->name . " برید ، میتوانید حتی وسیله راهبری را هم انتخاب کنید تا کوتاهترین مسیر محاسبه و به شما ارائه شود .";
    $pkeywords = "راهبری به " . $biz->name . ",navigate to " . $biz->name . "," . $biz->name . "در نقشه گوگل,map " . $biz->name . ",آدرس " . $biz->name;
    include_once("template/header.php");

    if ($biz->review_count !== "0") {
        $stars = $biz->review_sum / $biz->review_count;
        $haveHalf = is_float($stars) ? "half" : "0";
    } else {
        $stars = 0;
        $haveHalf = "";
    }
    if ($biz->primkey == "") {
        $primaryCode = $biz->city . $biz->bid;
    } else {
        $primaryCode = $biz->primkey;
    }


    if ($logged) {
        // USER FAV CATS
        $query_CheckPreviousFav = $conn->prepare('SELECT * FROM `ctg_usersFavCats` WHERE `uid`= :uid AND `cid` = :cid ');
        $query_CheckPreviousFav->bindValue(':uid', $user->user_id);
        $query_CheckPreviousFav->bindValue(':cid', $biz->subcat);
        $query_CheckPreviousFav->execute();

        if ($query_CheckPreviousFav->rowCount() == 0) {
            $query_AddFavCat = $conn->prepare('INSERT INTO `ctg_usersFavCats` VALUES (:uid,:cid,1) ');
            $query_AddFavCat->bindValue(':uid', $user->user_id);
            $query_AddFavCat->bindValue(':cid', $biz->subcat);
            $query_AddFavCat->execute();
        } else {
            $query_UpdateFavCat = $conn->prepare('UPDATE `ctg_usersFavCats` SET visits = visits+2 WHERE `uid`= :uid AND `cid` = :cid ');
            $query_UpdateFavCat->bindValue(':uid', $user->user_id);
            $query_UpdateFavCat->bindValue(':cid', $biz->subcat);
            $query_UpdateFavCat->execute();
        }
    }

    $eachLines = explode("\n", $biz->biz_des);
    $newLines = "";
    foreach ($eachLines as $eachLine) {
        $newLines = $newLines . "'" . $eachLine . "'+";
    }
    $newLines = substr($newLines, 0, strlen($newLines) - 1);


    $thispageScript = "
	<script type=\"text/javascript\" src=\"https://maps.google.com/maps/api/js?language=fa&key=AIzaSyBm91fjm5R1fL0zA9gDyD_sVqfuglbypCk\"></script>
	<script src=\"http://www.citygram.ir/vendor/jquery.gmap.js\"></script>
	<script>
		var map;
		var worklat = '" . $biz->lat . "';
		var worklng = '" . $biz->lng . "';
		var workname = '" . $biz->name . "';
		var workdes = '" . trim(preg_replace('/\s+/', ' ', $biz->biz_des)) . "';
		var workLink = '@" . $primaryCode . "';
		var workcomments = '" . $biz->review_count . "';
		
		
		$(document).ready(function() {
			map = new GMaps({
				div: '#map_canvas',
				lat: worklat,
				lng: worklng,
				disableDefaultUI: true,

				zoom: 14,
				zoomControl: true,
				zoomControlOptions: {
					position: google.maps.ControlPosition.LEFT_CENTER
				},

				mapTypeControl: true,
				mapTypeControlOptions: {
					style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
					position: google.maps.ControlPosition.TOP_LEFR
				},

				scaleControl: true,
				streetViewControl: false,

				fullscreenControl: true,
				fullscreenControl: {
					position: google.maps.ControlPosition.LEFT_TOP
				}

			});
			addWorkplace();
			$('input[name=prevLat]').val(worklat);
			$('input[name=prevLng]').val(worklng);
		 
			map.addControl({
				position: 'top_right',
				content: '<i class=\"fa fa-map-marker\"></i> محل فعلی شما',
				style: {
					margin: '5px',
					padding: '3px 6px',
					shadow: '2px 2px 1px #717B87',
					font: '14px normal  BYekan, B Yekan,\"byekan\",tahoma',
					color : '#fff',
					background: '#d95252'
				},
				events: {
					click: function() {
						map.removePolylines();
						GMaps.geolocate({
							success: function(position) {
								var userLat = position.coords.latitude;
								var userLng = position.coords.longitude;
								map.setZoom(15);
								map.setCenter(userLat, userLng);
								userPlaceMarker(userLat, userLng);
							},
							error: function(error) {
								alert('موقعیت سنجی ناموفق بود : ' + error.message);
							},
							not_supported: function() {
								alert('مرورگر شما از امکان موقعیت سنج برخوردار نیست !');
							}
						});
					}
				}
			});
		});
		
		function GetCurrentLocation(){
			map.removePolylines();
			GMaps.geolocate({
				success: function(position) {
					var userLat = position.coords.latitude;
					var userLng = position.coords.longitude;
					map.setZoom(15);
					map.setCenter(userLat, userLng);
					userPlaceMarker(userLat, userLng);
				},
				error: function(error) {
					alert('موقعیت سنجی ناموفق بود : ' + error.message);
				},
				not_supported: function() {
					alert('مرورگر شما از امکان موقعیت سنج برخوردار نیست !');
				}
			});
		}
	
		$('#CurrentLoc').on('change', function(e) {
				/*console.log(e.added.element.data('lat'));*/
			if(this.value == \"getCurrent\" )GetCurrentLocation();
			else{
				var lat = $(this).find(':selected').data('lat');
				var lng = $(this).find(':selected').data('lng');
				map.setZoom(15);
				map.setCenter(lat, lng);
				userPlaceMarker(lat , lng);
			}
		});
		function addWorkplace(){	
			var workPlace = map.addMarker({
				lat: worklat,
				lng: worklng,
				title: workname,
				infoWindow: {
					content: '<div class=\"marker-info-win\" >'+
					'<div class=\"marker-inner-win\" >'+
						'<span class=\"info-content\" >'+
							'<a href=\"'+siteurl+'/'+workLink+'\" target=\"_blank\"><h2 class=\"marker-heading byekan\" style=\"margin-top:2px;padding-bottom:6px;text-align:right;border-bottom:1px solid #ccc\">'+workname+'</h2></a>'+
							'<span class=\"label label-danger pull-left ssmall byekan\" ><a class=\"white\" href=\"'+siteurl+'/'+workLink+'\" target=\"_blank\">مشاهده صفحه</a></span>'+
							'<span class=\"label label-warning byekan ssmall\">نظرات : '+workcomments+' عدد</span>'+
						'</span>'+
					'</div>'+
				'</div>'+
				'<div class=\"mapInfoWindow small\">'+workdes+'</div>'
				}
			});
		}
		$('#goToDestionation').on('click', function() {
			map.setCenter(worklat, worklng);console.log('4');
		});
		
		function userPlaceMarker(latInput, lngInput) {
			map.removeMarkers();
			addWorkplace();
			console.log(latInput+':'+lngInput);
			var userPlace = map.addMarker({
				lat: latInput,
				lng: lngInput,
				draggable: true,
				title: 'محل شروع راهبری',
				infoWindow: {
					content: '<div class=\"marker-info-win\">'+
					'<div class=\"marker-inner-win\">'+
						'<span class=\"info-content irsans\">'+
							'<h4 class=\"marker-heading byekan small\" style=\"margin-top:2px;text-align:right;\"><i class=\"fa fa-map-marker\"></i> مبدا آغاز مسیر</h4>'+
							'<a class=\"byekan\" id=\"goToDestionation\"><i class=\"fa fa-flag\"></i> نمایش مقصد</a>'+
						'</span>'+
					'</div>'+
				'</div>'
				}
			});
			$('input[name=currentLat]').val(latInput);
			$('input[name=currentLng]').val(lngInput);

			google.maps.event.addListener(userPlace, 'dragend', function(evt) {
				var curlat = evt.latLng.lat().toFixed(3);
				var curlng = evt.latLng.lng().toFixed(3);
				$('input[name=currentLat]').val(curlat);
				$('input[name=currentLng]').val(curlng);
			});
			//google.maps.event.addListener(userPlace, \'dragstart\', function(evt) {
			//	$(\'#current\').html(\'<p>در حال انتخاب ...</p>\');
			//});
		}
		
		//google.maps.event.addListener(workPlace, 'click', function(evt) {
			//response
		//});
		
		
		// Search By Name
		$('#geocoding_form').submit(function(e) {
			map.removePolylines();
			e.preventDefault();
			GMaps.geocode({
				address: $('#address').val().trim(),
				callback: function(results, status) {
					if (status == 'OK') {
						var latlng = results[0].geometry.location;
						map.setCenter(latlng.lat(), latlng.lng());
						userPlaceMarker(latlng.lat(), latlng.lng());
						getNameOfLatLng(latLng.lat() + \",\" + latLng.lng());
					}
				}
			});
		});
		var kindofNavigate = \"driving\";
		$('input[type=radio]').on('ifChecked' , function(){
			kindofNavigate = $(this).val();
		});
		if(kindofNavigate == \"bicycling\"){
			alert('متاسفانه وسیله نقلیه دوچرخه در مسیر انتخابی مجاز نمی باشد .');
		}else if(kindofNavigate == \"transit\"){
			alert('متاسفانه در مسیر انتخابی مسیر راه آهن به درستی تعریف نشده است .');
		}
		// Navigate user
		$('#navigate').submit(function(e) {
			if($('input[name=currentLat]').val() == ''){
				alert('انتخاب مبدا ضروری است ...');
				return false;
			}	
			e.preventDefault();
			map.removePolylines();
			$('#instructions').html(\"\");
			$('instructionsCopy').fadeIn();
			map.setZoom(14);
			map.setCenter($('input[name=currentLat]').val(), $('input[name=currentLng]').val());
			map.travelRoute({
				origin: [$('input[name=currentLat]').val(), $('input[name=currentLng]').val()],
				destination: [worklat, worklng],
				travelMode: kindofNavigate ,
				step: function(e) {
					$('#instructions').append('<li>' + e.instructions + '</li>');
					$('#instructions li:eq(' + e.step_number + ')').delay(450 * e.step_number).fadeIn(200, function() {
						map.drawPolyline({
							path: e.path,
							strokeColor: '#131540',
							strokeOpacity: 0.6,
							strokeWeight: 6
						});
					});
				}
			});
		});
	</script>";
    ?>

    <style>
        #map_canvas {
            width: 100%;
            border: 2px solid #ccc;
            margin-bottom: 10px;
            height: 300px;
        }

        #instructions {
            padding-right: 5px;
            list-style-type: none
        }

        .mapInfoWindow {
            min-width: 250px;
            text-align: right;
            margin: 0 !important;
            padding: 0 !important
        }

        .mapInfoWindow {
        }
    </style>
    <div role="main" class="main">
    <section class="page-top basic">
        <div class="page-top-info container" style="line-height:auto">
            <div class="row">
                <div class="col-md-4 col-xs-12 col-sm-3 pull-left" style="text-align:left;">
                    <div>
                        <a href="<?= $siteurl; ?>/@<?= $primkey; ?>/addPic" class="btn3d btn-danger btn-md pb5"><i
                                class="fa fa-photo ml10"></i> افزودن عکس</a>
                    </div>
                </div>

                <div class="col-md-8 col-xs-12 profTitle" style="text-align:right;">
                    <a href="<?= $siteurl; ?>/@<?= $primkey; ?>" class="white"><h3
                            class="white biz-title"><?= $biz->name; ?></h3></a>

                    <?
                    if ($biz->review_count !== "0") {
                        $stars = $biz->review_sum / $biz->review_count;
                        $haveHalf = is_float($stars) ? "half" : "";
                    } else {
                        $stars = 0;
                        $haveHalf = "";
                    }
                    $ctname = locdata($biz->city)->local_name;
                    ?>
                    <span class="rating-input gold mr10 lgsize"
                          data-stars="<?= ceil($stars); ?>-5<?= $haveHalf; ?>"></span> <?= $biz->review_count; ?> <span
                        class="small">رای (مجموع : <?= $biz->review_sum == "" ? "0" : $biz->review_sum; ?>)</span><br/>
                    <i class="fa fa-map-marker ml10"></i><a href="<?= $siteurl; ?>/search/city=<?= $ctname; ?>/"
                                                            class="white"><?= $ctname; ?></a> - <a
                        href="<?= $siteurl; ?>/search/city=<?= $ctname; ?>-subct=<?= $biz->subcity; ?>"
                        class="white"><?= citydata($biz->subcity)->name; ?></a>
                </div>
            </div>
            <br/>
        </div>
    </section>
    <br class="clear"/>
    <div class="container mb15">
        <div class="row">
            <div class="col-md-4">
                <div class="prof-biz-reserve">
                    <span class="small" style="">ابتدا نقطه آغاز را انتخاب و سپس روی راهبری کلیک کنید.</span>
                </div>
                <div class="mt15">
                    نقطه آغاز :
                    <select id="CurrentLoc" class="noselect" style="width:50%">
                        <option value="0">انتخاب کنید ...</option>
                        <?
                        if ($logged) {
                            $query_userPlaces = $conn->prepare("SELECT * FROM `ctg_usersPlaces` WHERE `uid` = :uid ");
                            $query_userPlaces->bindValue(':uid', $user->user_id);
                            $query_userPlaces->execute();
                            $userPlaces = $query_userPlaces->fetchAll();
                            foreach ($userPlaces as $userPlace) { ?>
                                <option data-lat="<?= $userPlace['lng']; ?>" data-lng="<?= $userPlace['lat']; ?>"
                                        value="<?= $userPlace['id']; ?>"><?= $userPlace['title']; ?></option>
                            <? }
                        } ?>
                        <option value="getCurrent">محل فعلی شما</option>
                    </select><? if ($logged) { ?>
                        <a href="<?= $siteurl; ?>/usercp/addNewPlace/" class="mr10 inline"><i
                                class="fa fa-map-marker"></i> تعریف مکان</a>
                    <? } ?>
                    <hr/>
                    مقصد : <a id="goToDestionation"><?= $biz->name; ?></a>
                    <hr/>
                    <form id="navigate">
                        <div class="mt15">
                            راهبری با :
                            <label class="ml5"><input type="radio" name="kindofNavigate" value="driving" checked/> <i
                                    class="fa fa-car lgsize mr5"></i></label>
                            <label class="ml5"><input type="radio" name="kindofNavigate" value="bicycling"/> <i
                                    class="fa fa-bicycle llgsize mr5"></i></label>
                            <label class="ml5"><input type="radio" name="kindofNavigate" value="transit"/> <i
                                    class="fa fa-subway llgsize mr5"></i></label>
                            <label class="ml5"><input type="radio" name="kindofNavigate" value="walking"/> <i
                                    class="fa fa-male llgsize mr5"></i></label>
                        </div>
                        <input type="hidden" value="" name="currentLat"/>
                        <input type="hidden" value="" name="currentLng"/>
                        <input type="hidden" value="" name="prevLat"/>
                        <input type="hidden" value="" name="prevLng"/>
                        <button class="btn3d btn-info mt15" type="submit">شروع راهبری</button>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <div id='map_canvas'></div>
            </div>
            <div id="instructions"></div>
            <button id="instructionsCopy" style="display:none" class="btn3d btn-info">کپی متن</button>
        </div>
    </div>
    <br/>

<? } else {
	$missTyping = fa2enString($biz->name) ;
$canonical = '<link rel="canonical" href="http://www.citygram.ir/@'.$primkey.'/'.str_replace(" ", "-", $biz->name).'" />';
    $ptitle = $biz->name . " - " . $ctname . " - سیتی گرام";
    $pdes = $biz->biz_des . " - آدرس : " . $biz->address . "- شماره تماس :" . $biz->phone;
    $pkeywords = $biz->name . ",سیتی گرام,صفحه کسب و کارهای سیتی گرام,".$missTyping;
    $image = "http://citygramcdn.ir/bphoto/" . urlencode(imageEncrypt($biz->photo . ':" ":::1')) . "/220.ctg";

    include_once("template/header.php");


    if ($logged) {
        // USER FAV CATS
        $query_CheckPreviousFav = $conn->prepare('SELECT * FROM `ctg_usersFavCats` WHERE `uid`= :uid AND `cid` = :cid ');
        $query_CheckPreviousFav->bindValue(':uid', $user->user_id);
        $query_CheckPreviousFav->bindValue(':cid', $biz->subcat);
        $query_CheckPreviousFav->execute();

        if ($query_CheckPreviousFav->rowCount() == 0) {
            $query_AddFavCat = $conn->prepare('INSERT INTO `ctg_usersFavCats` VALUES (:uid,:cid,1) ');
            $query_AddFavCat->bindValue(':uid', $user->user_id);
            $query_AddFavCat->bindValue(':cid', $biz->subcat);
            $query_AddFavCat->execute();
        } else {
            $query_UpdateFavCat = $conn->prepare('UPDATE `ctg_usersFavCats` SET visits = visits+1 WHERE `uid`= :uid AND `cid` = :cid ');
            $query_UpdateFavCat->bindValue(':uid', $user->user_id);
            $query_UpdateFavCat->bindValue(':cid', $biz->subcat);
            $query_UpdateFavCat->execute();
        }
    }


    //Coments Count
    $query_pcms = $conn->prepare('SELECT * FROM ctg_comments WHERE bid = :bid AND activeperm = 1 AND answerKind <> "prdc" ORDER BY date DESC ');
    $query_pcms->bindValue(':bid', $biz->bid);
    $query_pcms->execute();
    $bizComments = $query_pcms->fetchAll();
    $bizCommentsList = "";
    foreach ($bizComments as $pcomment) {
        $biz = bizdata($pcomment['bid']);
        if ($pcomment['answerfor'] == NULL) {
            $bizCommentsList .= "<a href='" . $siteurl . "/@" . $pcomment['bid'] . "/" . $biz->name . "' title='" . $biz->name . "' >" . $biz->name . "</a> - ";
        }
    }


    // ReserveActivePerm - productActivePerm - BizDailyProgramShow - BizFollowShow - BizLikeShow - BizReserveShow
    if ($biz->timeline !== "") {
        $tml_settings = explode(':', $biz->timeline);
        $ReserveActivePerm = $tml_settings[0];
        $productActivePerm = $tml_settings[1];
        $BizDailyProgramShow = $tml_settings[2];
        $BizFollowShow = $tml_settings[3];
        $BizLikeShow = $tml_settings[4];
        $BizReserveShow = $tml_settings[5];
        $BizCommentShow = $tml_settings[6];
        $letAddComment = $tml_settings[7];
    } else {
        $ReserveActivePerm = 1;
        $productActivePerm = 1;
        $BizDailyProgramShow = 1;
        $BizFollowShow = 1;
        $BizLikeShow = 1;
        $BizReserveShow = 0;
        $BizCommentShow = 1;
        $letAddComment = 1;
    }

    if ($productActivePerm) {
        $productPart = "UNION SELECT `kind` as type,`content` as data,`image` as uid,bpid as bid ,adddate as date FROM `ctg_BizProducts` WHERE `bid` = :bid ";
    }
    if ($BizFollowShow) {
        $followPart = "UNION Select 'follow' as type, -1 as data , uid , bid as bid , date from `ctg_follows` where bid = :bid ";
    }
    if ($BizLikeShow) {
        $likePart = "UNION Select 'like' as type, 'none' as data , uid , bid , date from `ctg_likes` where bid = :bid ";
    }
    if ($BizReserveShow) {
        $reservationPart = "UNION Select 'reservation' as type, menu as data, uid , bid , date from `ctg_reservation` where bid = :bid ";
    }
    if ($BizCommentShow) {
        $commentPart = "UNION Select 'comment' as type, comment as data , user as uid , cid as bid , date from `ctg_comments` where bid = :bid  AND answerKind <> 'prdc' ";
    }

    //TIMELINE Creator
    $query_timeline = $conn->prepare("Select 'photo' as type, address as data , byuid as uid , id as bid , date from `ctg_photos` where bid = :bid 
	$commentPart 
	$likePart 
	$followPart 
	$reservationPart 
	Order by date DESC limit 12");
    $query_timeline->bindValue(':bid', $biz->bid);
    $query_timeline->execute();
    $timelines = $query_timeline->fetchAll();

    ?>

    <div role="main" class="main">

    <section class="page-top basic">
        <div class="page-top-info container" style="height:260px;line-height:auto">
            <div class="row">
                <div class="col-md-4 col-xs-12 col-sm-3 pull-left" style="text-align:left;">
                    <div>
                        <? if ($logged) {
                            if ($biz->owner == $user->user_id) {
                                ?>

                                <a href="<?= $siteurl; ?>/BusinessCP/stats-<?= $primkey; ?>"
                                   class="btn3d btn-danger btn-md pb5"><i class="fa fa-briefcase ml10"></i> پنل
                                    مدیریت</a>
                                <a href="<?= $siteurl; ?>/BusinessCP/photos-<?= $primkey; ?>" rel="tooltip"
                                   title="مدیریت تصاویر" data-toggle="tooltip" date-placement="bottom"
                                   class="btn3d btn-info btn-sm"><i class="fa fa-photo"></i></a>
                                <a href="<?= $siteurl; ?>/BusinessCP/stats-<?= $primkey; ?>" rel="tooltip"
                                   title="آمار کلی" data-toggle="tooltip" date-placement="bottom"
                                   class="btn3d btn-info btn-sm"><i class="fa fa-line-chart"></i></a>
                                <a href="<?= $siteurl; ?>/BusinessCP/setting-<?= $primkey; ?>" rel="tooltip"
                                   title="تنظیمات" data-toggle="tooltip" date-placement="bottom"
                                   class="btn3d btn-info btn-sm"><i class="fa fa-cog"></i></a>

                            <? } else {

                                // Check If user Followed Business
                                $query_checkFollowed = $conn->prepare("SELECT * FROM `ctg_follows` WHERE `uid` = :uid AND `bid` = :bid ");
                                $query_checkFollowed->bindValue(":uid", $user->user_id);
                                $query_checkFollowed->bindValue(":bid", $biz->bid);
                                $query_checkFollowed->execute();
                                if ($query_checkFollowed->rowCount() == 0) {
                                    $FollowButton = '<a data-href="' . $siteurl . '/model/?key=' . encryptIt("doFollowBussiness_" . random_string(8)) . '&csrf_token=' . getCSRFToken() . '&bid=' . $biz->bid . '" rel="tooltip" data-res="#ctgAjaxLinkFollowResult" id="ctgAjaxLinkFollowResult" title="دنبال کردن" data-toggle="tooltip" date-placement="bottom" class="btn3d btn-primary btn-sm ctgAjaxLink"><i class="fa fa-bookmark"></i><i class="mr5 fa fa-plus green ssmall"></i></a>';
                                } else {
                                    $FollowButton = '<a data-href="' . $siteurl . '/model/?key=' . encryptIt("doUnFollowBussiness_" . random_string(8)) . '&csrf_token=' . getCSRFToken() . '&bid=' . $biz->bid . '" rel="tooltip" data-res="#ctgAjaxLinkFollowResult" id="ctgAjaxLinkFollowResult" title="قطع دنبال کردن" data-toggle="tooltip" date-placement="bottom" class="btn3d btn-primary btn-sm ctgAjaxLink"><i class="fa fa-bookmark"></i><i class="mr5 fa fa-minus ssmall"></i></a>';
                                }

                                // Check If user liked Business
                                $query_checkLiked = $conn->prepare("SELECT * FROM `ctg_likes` WHERE `uid` = :uid AND `bid` = :bid ");
                                $query_checkLiked->bindValue(":uid", $user->user_id);
                                $query_checkLiked->bindValue(":bid", $biz->bid);
                                $query_checkLiked->execute();

                                if ($query_checkLiked->rowCount() == 0) {
                                    $LikeButton = '<a data-href="' . $siteurl . '/model/?key=' . encryptIt("doLikeBussiness_" . random_string(8)) . '&csrf_token=' . getCSRFToken() . '&bid=' . $biz->bid . '" rel="tooltip" data-res="#ctgAjaxLinkLikeResult" id="ctgAjaxLinkLikeResult" title="پسند کردن" data-toggle="tooltip" date-placement="bottom" class="btn3d btn-danger btn-sm ctgAjaxLink"><i class="fa fa-heart"></i><i class="mr5 fa fa-plus green ssmall"></i></a>';
                                } else {
                                    $LikeButton = '<a data-href="' . $siteurl . '/model/?key=' . encryptIt("doUnLikeBussiness_" . random_string(8)) . '&csrf_token=' . getCSRFToken() . '&bid=' . $biz->bid . '" rel="tooltip" data-res="#ctgAjaxLinkLikeResult" id="ctgAjaxLinkLikeResult" title="لغو پسند" data-toggle="tooltip" date-placement="bottom" class="btn3d btn-danger btn-sm ctgAjaxLink"><i class="fa fa-heart"></i><i class="mr5 fa fa-minus ssmall"></i></a>';
                                }
                                ?>

                                <a href="#onlineReservation" data-toggle="modal" class="btn3d btn-danger btn-md pb5"><i
                                        class="fa fa-shopping-bag ml10"></i> رزرو کردن</a>
                                <a href="<?= $siteurl; ?>/@<?= $primkey; ?>/photos/گالری_عکس_<?= str_replace(" ", "-", $biz->name); ?>"
                                   rel="tooltip" title="گالری عکس" data-toggle="tooltip" date-placement="bottom"
                                   class="btn3d btn-info btn-sm"><i class="fa fa-photo"></i> تصاویر</a>
                                <?= $FollowButton; ?>
                                <?= $LikeButton; ?>
                            <? }
                        } else { ?>
                            <a href="<?= $siteurl; ?>/@<?= $primkey; ?>/photos/گالری_عکس_<?= str_replace(" ", "-", $biz->name); ?>"
                               rel="tooltip" title="گالری عکس" data-toggle="tooltip" date-placement="bottom"
                               class="btn3d btn-danger btn-md"><i class="fa fa-photo"></i> تصاویر</a>
                        <? } ?>

                    </div>
                </div>
                <div class="col-md-8 col-xs-12 col-md-9 pull-right profTitle" style="text-align:right;">
                    <h3 class="white biz-title"><?= $biz->name; ?></h3>
                    <?
                    if ($biz->review_count !== "0") {
                        $stars = $biz->review_sum / $biz->review_count;
                        $haveHalf = is_float($stars) ? "half" : "";
                    } else {
                        $stars = 0;
                        $haveHalf = "";
                    }
                    $ctname = locdata($biz->city)->local_name;
                    ?>
                    <span class="rating-input gold mr10 lgsize"
                          data-stars="<?= ceil($stars); ?>-5<?= $haveHalf; ?>"></span> <?= $biz->review_count; ?> <span
                        class="small">رای (مجموع : <?= $biz->review_sum == "" ? "0" : $biz->review_sum; ?>)</span><br/>
                    <a href="" class="white"><i class="fa fa-map-marker ml10"></i><a
                            href="<?= $siteurl; ?>/search/city=<?= $ctname; ?>/" class="white"><?= $ctname; ?></a> - <a
                            href="<?= $siteurl; ?>/search/city=<?= $ctname; ?>-subct=<?= $biz->subcity; ?>"
                            class="white"><?= citydata($biz->subcity)->name; ?></a>
                </div>
            </div>
            <div class="container">
                <div class="row">

                    <!-- Map part start -->
                    <div class="col-md-4  col-xs-12 pull-right"
                         style="border-radius:6px;background:#fff;color:#555;margin-top:10px;padding:5px;text-align:right;">
                        <img
                            src="https://maps.googleapis.com/maps/api/staticmap?language=fa&center=<?= $biz->lat; ?>,<?= $biz->lng; ?>&size=400x200&sensor=true&zoom=16&markers=color:red|<?= $biz->lat; ?>,<?= $biz->lng; ?>&scale=1"
                            id="pagepic" class="rad5" style="width:100%;display:block;height:auto"/>
                        <span class="ssmall"><?= $biz->address; ?></span>
                        <div class="row">
                            <div class="col-md-6 small">
                                <a href="<?= $siteurl; ?>/@<?= $primkey; ?>/navigate/راهبری-به-<?= str_replace(" ", "-", $biz->name); ?>"><i
                                        class="fa fa-map-marker"></i> راهبری به این مکان</a>
                            </div>
                            <div class="col-md-6 ssmall">
                                شماره تماس : <?= $biz->phone; ?>
                            </div>
                        </div>
                        <?= $biz->website == "" ? "" : 'وبسایت : <a href="' . $biz->website . '" target="_blank" rel="nofollow">لینک</a>'; ?>
                    </div>
                    <!-- Map part closed -->

                    <!-- Slider start-->
                    <div class="col-md-6 col-xs-12">
                        <?php
                        if ($query_pcms->rowCount() > 0) {
                            ?>
                            <div class="carousel slide" data-ride="carousel" id="quote-carousel">
                                <div class="carousel-inner">
                                    <?php
                                    $cmID = 1;
                                    foreach ($bizComments as $bizComment) {
                                        $cmUser = userdata($bizComment['user'], 'realname,avatar');
                                        // User avatar load
                                        if ($cmUser->avatar == NULL) $cmavatar = "avatars/default.png";
                                        else $cmavatar = $cmUser->avatar;

                                        if ($cmID > 10) {
                                            break;
                                        }
                                        ?>

                                        <div class="item <?= $cmID == 1 ? "active" : ""; ?>">
                                            <blockquote>
                                                <div class="row">
                                                    <div class="col-sm-9">
                                                        <div
                                                            class="avgsize"><?= html_entity_decode($bizComment['comment']); ?></div>
                                                        <a class="green lgsize mt5"
                                                           href="<?= $siteurl; ?>/profile/<?= $bizComment['user']; ?>/<?= str_replace(" ", "-", $cmUser->realname); ?>"
                                                           target="_blank">
                                                            <small><?= $cmUser->realname; ?></small>
                                                        </a>
                                                    </div>
                                                    <div class="col-sm-3 text-center">
                                                        <img class="img-circle"
                                                             src="http://citygramcdn.ir/avatar/<?= urlencode(imageEncrypt($cmavatar)); ?>/100.ctg"
                                                             style="width: 100px;height:100px;"/>
                                                    </div>
                                                </div>
                                            </blockquote>
                                        </div>

                                        <? $cmID++;
                                    } ?>
                                </div>

                                <!-- Carousel Buttons Next/Prev -->
                                <a data-slide="prev" href="#quote-carousel" class="left carousel-control"
                                   style="padding-top:12% !important"><i class="fa fa-chevron-left"></i></a>
                                <a data-slide="next" href="#quote-carousel" class="right carousel-control"
                                   style="padding-top:12% !important"><i class="fa fa-chevron-right"></i></a>
                            </div>
                        <? } else {
                            // بدون نظر
                        } ?>
                    </div>
                    <div class="col-md-2"></div>
                </div>    <!-- close of row-->
            </div> <!-- close of page Top container-->
        </div>
    </section>
    <br class="clear"/>
    <div class="container mt15">
        <div class="row">
            <div class="col-md-4 col-xs-12">
                <div class="prof-biz-reserve">
                    <i class="fa fa-clock-o"></i>
                    <div class="biz-content">
                        <?php
                        function toDateName($todayProgram)
                        {
                            $timename = "";
                            if (!empty($todayProgram)) {
                                if ($todayProgram > 19 && $todayProgram < 24)
                                    $timename = "شب";
                                else if ($todayProgram >= 16 && $todayProgram < 19)
                                    $timename = "عصر";
                                else if ($todayProgram >= 11 && $todayProgram < 16)
                                    $timename = "ظهر";
                                else if ($todayProgram >= 5 && $todayProgram < 11)
                                    $timename = "صبح";
                                else if ($todayProgram >= 0 && $todayProgram < 5)
                                    $timename = "بامداد";
                                else
                                    $timename = "شب";
                            }
                            return $timename;
                        }


                        $program = explode(',', $biz->businessProgram);
                        $day1 = $day2 = $day3 = $day4 = $day5 = $day6 = $day7 = "";
                        if ($program[0] == 0 && $program[1] == 0) {
                            $program[0] = "";
                            $program[1] = "";
                            $day1 = "تعطیل";
                        }
                        if ($program[2] == 0 && $program[3] == 0) {
                            $day2 = "تعطیل";
                            $program[2] = "";
                            $program[3] = "";
                        }
                        if ($program[4] == 0 && $program[5] == 0) {
                            $day3 = "تعطیل";
                            $program[4] = "";
                            $program[5] = "";
                        }
                        if ($program[6] == 0 && $program[7] == 0) {
                            $day4 = "تعطیل";
                            $program[6] = "";
                            $program[7] = "";
                        }
                        if ($program[8] == 0 && $program[9] == 0) {
                            $day5 = "تعطیل";
                            $program[8] = "";
                            $program[9] = "";
                        }
                        if ($program[10] == 0 && $program[11] == 0) {
                            $day6 = "تعطیل";
                            $program[10] = "";
                            $program[11] = "";
                        }
                        if ($program[12] == 0 && $program[13] == 0) {
                            $day7 = "تعطیل";
                            $program[12] = "";
                            $program[13] = "";
                        }

                        $dayOfWeekStr = jdate('w:G', $now);
                        $todayWeekAndHour = explode(':', $dayOfWeekStr); // $todayWeekAndHour[0] ==> Week  # $todayWeekAndHour[1] ==> hour
                        $tommorrowProgram = $program[($todayWeekAndHour[0] * 2)];
                        $todayProgram = $program[($todayWeekAndHour[0] * 2) + 1];
                        if ($tommorrowProgram == 0 && $todayProgram == 0)
                            $todayTime = "امروز تعطیل هستیم .";
                        else {
                            $closed = $betweeenText = "";


                            if ($tommorrowProgram > $todayWeekAndHour[1]) $betweeenText = "از " . $tommorrowProgram . " صبح";


                            if ($todayProgram > 0 && $todayProgram < 7 && $todayWeekAndHour[1] > 7) $todayProgramNew = $todayProgram + 23;
                            else
                                $todayProgramNew = $todayProgram;

                            if ($todayWeekAndHour[1] >= $todayProgramNew) $closed = "بودیم <span class='ssmall red'>الان بسته ایم .</span>";
                            else $closed = "هستیم .";
                            $todayTime = "امروز " . $betweeenText . " تا " . $todayProgram . " " . toDateName($todayProgram) . " باز " . $closed;
                        }
                        ?>

                        <span class="small"><?= $todayTime; ?></span>
                        <div class="mt10" style="text-align:center">
                            <? if ($ReserveActivePerm) { ?><a href="#onlineReservation" data-toggle="modal"
                                                              class="btn btn-dodored"><i class="fa fa-shopping-bag"></i>
                                رزرو آنلاین</a><? } ?>
                            <a href="<?= $siteurl; ?>/@<?= $primkey; ?>/navigate/راهبری_به_<?= $biz->name; ?>"
                               class="btn btn-dodored pb5"><i class="fa fa-map-marker"></i> یافتن روی نقشه</a>
                        </div>
                    </div>
                </div>
                <? if ($BizDailyProgramShow) { ?>
                    <div class="mt15 panel panel-default bt-rd4">
                        <div class="panel-body">
                            <div class="llgsize red mb10" style="border-bottom:1px dashed #f99595;padding-bottom:10px">
                                <? if ($logged) if ($biz->owner == $user->user_id) { ?>

                                    <div class="pull-left mb5"><a
                                            href="<?= $siteurl; ?>/BusinessCP/editWeeklyProgram-<?= $primkey; ?>"><i
                                                class="fa fa-pencil"></i></a></div>

                                <? } ?>
                                ساعات کاری
                            </div>
                            <div
                                class="row <?= $todayWeekAndHour[0] == 0 ? 'green" rel="tooltip" data-placement="left" title="امروز' : ""; ?>">
                                <div class="col-md-3 col-xs-3 red ssmall"><?= $day1; ?></div>
                                <div
                                    class="col-md-4 col-xs-4 ssmall"><?= $program[0] . " <span class='hide500'>" . toDateName($program[0]) . "</span>"; ?>
                                    - <?= $program[1] . " <span class='hide500'>" . toDateName($program[1]) . "</span>"; ?></div>
                                <div class="col-md-2 col-xs-2 ssmall">ساعت:</div>
                                <div class="col-md-3 col-xs-3 ssmall">شنبه</div>
                            </div>
                            <div
                                class="row <?= $todayWeekAndHour[0] == 1 ? 'green" rel="tooltip" data-placement="left" title="امروز' : ""; ?>">
                                <div class="col-md-3 col-xs-3 red ssmall"><?= $day2; ?></div>
                                <div
                                    class="col-md-4 col-xs-4 ssmall"><?= $program[2] . " <span class='hide500'>" . toDateName($program[2]) . "</span>"; ?>
                                    - <?= $program[3] . " <span class='hide500'>" . toDateName($program[3]) . "</span>"; ?></div>
                                <div class="col-md-2 col-xs-2 ssmall">ساعت:</div>
                                <div class="col-md-3 col-xs-3 ssmall">یکشنبه</div>
                            </div>
                            <div
                                class="row <?= $todayWeekAndHour[0] == 2 ? 'green" rel="tooltip" data-placement="left" title="امروز' : ""; ?>">
                                <div class="col-md-3 col-xs-3 red ssmall"><?= $day3; ?></div>
                                <div
                                    class="col-md-4 col-xs-4 ssmall"><?= $program[4] . " <span class='hide500'>" . toDateName($program[4]) . "</span>"; ?>
                                    - <?= $program[5] . " <span class='hide500'>" . toDateName($program[5]) . "</span>"; ?></div>
                                <div class="col-md-2 col-xs-2 ssmall">ساعت:</div>
                                <div class="col-md-3 col-xs-3 ssmall">دوشنبه</div>
                            </div>
                            <div
                                class="row <?= $todayWeekAndHour[0] == 3 ? 'green" rel="tooltip" data-placement="left" title="امروز' : ""; ?>">
                                <div class="col-md-3 col-xs-3 red ssmall"><?= $day4; ?></div>
                                <div
                                    class="col-md-4 col-xs-4 ssmall"><?= $program[6] . " <span class='hide500'>" . toDateName($program[6]) . "</span>"; ?>
                                    - <?= $program[7] . " <span class='hide500'>" . toDateName($program[7]) . "</span>"; ?></div>
                                <div class="col-md-2 col-xs-2 ssmall">ساعت:</div>
                                <div class="col-md-3 col-xs-3 ssmall">سه شنبه</div>
                            </div>
                            <div
                                class="row <?= $todayWeekAndHour[0] == 4 ? 'green" rel="tooltip" data-placement="left" title="امروز' : ""; ?>">
                                <div class="col-md-3 col-xs-3 red ssmall"><?= $day5; ?></div>
                                <div
                                    class="col-md-4 col-xs-4 ssmall"><?= $program[8] . " <span class='hide500'>" . toDateName($program[8]) . "</span>"; ?>
                                    - <?= $program[9] . " <span class='hide500'>" . toDateName($program[9]) . "</span>"; ?></div>
                                <div class="col-md-2 col-xs-2 ssmall">ساعت:</div>
                                <div class="col-md-3 col-xs-3 ssmall">چهارشنبه</div>
                            </div>
                            <div
                                class="row <?= $todayWeekAndHour[0] == 5 ? 'green" rel="tooltip" data-placement="left" title="امروز' : ""; ?>">
                                <div class="col-md-3 col-xs-3 red ssmall"><?= $day6; ?></div>
                                <div
                                    class="col-md-4 col-xs-4 ssmall"><?= $program[10] . " <span class='hide500'>" . toDateName($program[10]) . "</span>"; ?>
                                    - <?= $program[11] . " <span class='hide500'>" . toDateName($program[11]) . "</span>"; ?></div>
                                <div class="col-md-2 col-xs-2 ssmall">ساعت:</div>
                                <div class="col-md-3 col-xs-3 ssmall">پنج شنبه</div>
                            </div>

                            <div
                                class="row <?= $todayWeekAndHour[0] == 6 ? 'green" rel="tooltip" data-placement="left" title="امروز' : ""; ?>">
                                <div class="col-md-3 col-xs-3 red ssmall"><?= $day7; ?></div>
                                <div
                                    class="col-md-4 col-xs-4 ssmall"><?= $program[12] . " <span class='hide500'>" . toDateName($program[12]) . "</span>"; ?>
                                    - <?= $program[13] . " <span class='hide500'>" . toDateName($program[13]) . "</span>"; ?></div>
                                <div class="col-md-2 col-xs-2 ssmall">ساعت:</div>
                                <div class="col-md-3 col-xs-3 ssmall">جمعه</div>
                            </div>
                        </div>
                    </div>
                <? } ?>
                <?
                if ($biz->first_comment !== NULL) {
                    // $userOF_Fc =
                    $fcUser = userdata($biz->first_comment, 'realname'); ?>
                    <div class="mt15 panel panel-default bt-rd4">
                        <div class="panel-body">
                            اولین نظر :
                            <a href="<?= $siteurl; ?>/profile/<?= $biz->first_comment; ?>/<?= str_replace(" ", "-", $fcUser->realname); ?>"
                               class="mr5 red avgsize">
                                <i class="fa llgsize fa-paw ml5"></i> <?= $fcUser->realname; ?>
                            </a>
                        </div>
                    </div>
                <? } ?>
                <div class="mt15 panel panel-default bt-rd4">
                    <div class="panel-body" style="text-align:justify">
                        <div class="avgsize red mb10" style="border-bottom:1px dashed #ccc;padding-bottom:10px">
                            <? if ($logged) if ($biz->owner == $user->user_id) { ?>

                                <div class="pull-left mb5"><a
                                        href="<?= $siteurl; ?>/BusinessCP/editBiz-<?= $primkey; ?>"><i
                                            class="fa fa-pencil"></i></a></div>

                            <? } ?>
                            توضیحات کسب و کار
                        </div>
                        <?= $biz->biz_des; ?>
                    </div>
                </div>

                <div class="carousel slide" id="myCarousel" style="clear:both">
                    <!-- Carousel items (Five Random pics) -->

                    <div class="carousel-inner">
                        <?php
                        $query_pics = $conn->prepare("SELECT address FROM `ctg_photos` WHERE `bid` = :bid ORDER BY RAND() LIMIT 8");
                        $query_pics->bindValue(':bid', $biz->bid);
                        $query_pics->execute();
                        $userPics = $query_pics->fetchAll();
                        $pidId = 1;
                        if (count($userPics) > 0) {
                            foreach ($userPics as $userPic) {
                                ?>
                                <div class="<? if ($pidId == 1) echo 'active ';; ?>item"
                                     data-slide-number="<?= $pidId; ?>">
                                    <a href="<?= $siteurl; ?>/@<?= $primkey; ?>/photos/گالری-عکس-<?= str_replace(" ", "-", $biz->name); ?>"><img
                                            class="rad5"
                                            src="http://citygramcdn.ir/bphoto/<?= urlencode(imageEncrypt($userPic['address'] . ':::16:1')); ?>/360.ctg"
                                            alt="<?= $biz->name; ?>" style="width:360px;height:360px"></a>
                                </div>
                                <?php
                                ++$pidId;
                            }
                        } else { ?>
                            <div class="<? if ($pidId == 1) echo 'active ';; ?>item" data-slide-number="<?= $pidId; ?>">
                                <a href="<?= $siteurl; ?>/@<?= $primkey; ?>/addPic/افزودن-عکس-به-<?= str_replace(" ", "-", $biz->name); ?>"><img
                                        class="rad5"
                                        src="http://citygramcdn.ir/bphoto/<?= urlencode(imageEncrypt('usrUpload/text.png:" ":::1')); ?>/360.ctg"
                                        alt="بدون عکس" style="width:360px;height:360px"></a>
                            </div>

                        <? } ?>
                    </div>
                    <!-- Carousel nav -->
                    <a class="left carousel-control" style="left:15px !important" href="#myCarousel" role="button"
                       data-slide="prev">
                        <span class="fa fa-arrow-circle-left gray" style="font-size:40px !important"></span>
                    </a>
                    <a class="right carousel-control" style="right:8px !important" href="#myCarousel" role="button"
                       data-slide="next">
                        <span class="fa fa-arrow-circle-right gray" style="font-size:40px !important"></span>
                    </a>
                </div>
                <div class="mt15 panel panel-default bt-rd4">
                    <div class="panel-body">
                        <a href="<?= $siteurl; ?>/@<?= $primkey; ?>/photos/گالری-عکس-<?= str_replace(" ", "-", $biz->name); ?>"
                           class="mr5 grey avgsize">
                            <i class="fa llgsize fa-photo ml5"></i> مشاهده تمام تصاویر
                        </a>
                    </div>
                </div>

                <div class="panel-body newgrams" style="margin-top:0">
                    <div class="mt15 panel panel-default bt-rd4">
                        <div class="panel-heading">
                            <h4>همچنین پیشنهاد می کنیم :</h4>
                        </div>
                        <div class="panel-body newgrams">
                            <?php
                            $bussinsses = loadbiz('newest', 8, 'primkey,city,bid,name,photo');
                            foreach ($bussinsses as $bussinss) {
                                if ($bussinss['photo'] !== "") {
                                    $bizPhoto = $bussinss['photo'];
                                } else {
                                    $bizPhoto = "usrUpload/text.png";
                                }
                                ?>
                                <a href="<?= $siteurl; ?>/@<?= $bussinss['primkey'] == "" ? $bussinss['city'] . $bussinss['bid'] : $bussinss['primkey']; ?>/<?= str_replace(" ", "-", $bussinss['name']); ?>"
                                   title="<?= $bussinss['name']; ?>" target="_blank">
                                    <img
                                        src="http://citygramcdn.ir/bphoto/<?= urlencode(imageEncrypt($bizPhoto . ':" "::20')); ?>/18.ctg"
                                        class="ml5" alt="<?= $bussinss['name']; ?>"
                                        style="width:20px;height:20px"/> <?= $bussinss['name']; ?>
                                </a>
                            <? } ?>
                        </div>
                    </div>
                </div>
				<div class="panel panel-default bt-rd4">
					<div class="panel-body">
					   با اشتباه تایپی :<br/>
					   <?=$missTyping;?>
					   <br/>
					</div>
			   </div>

                اشتراک گذاری این صفحه در : <br/>
                <div class="mt5 mb20">
                    <a href="https://plus.google.com/share?url=<?= $siteurl . "/@" . $primkey; ?>" target="_blank"
                       title="" rel="nofollow"><i class="fa fa-google-plus-square"
                                                  style="color:#DD4B39;font-size:38px"></i></a>
                    <a href="http://www.facebook.com/sharer.php?u=<?= $siteurl . "/@" . $primkey; ?>" target="_blank"
                       rel="nofollow"><i class="fa fa-facebook-square" style="color:#3B5998;font-size:38px"></i></a>
                    <a href="http://twitter.com/home?status=Currently reading <?= $biz->name . " See at:" . $siteurl . "/@" . $primkey; ?>"
                       target="_blank" rel="nofollow"><i class="fa fa-twitter-square"
                                                         style="color:#55ACEE;font-size:38px"></i></a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true<?= "&title=" . $biz->name . "&url=" . $siteurl . "/@" . $primkey; ?>&summary=<?= $biz->address . "-" . $biz->biz_des; ?>&source=citygram.ir"
                       target="_blank" rel="nofollow"><i class="fa fa-linkedin-square"
                                                         style="color:#3b5998;font-size:38px"></i></a>
                </div>


            </div>

            <style>
                #loader {
                    display: none;
                    position: fixed;
                    right: 50%;
                    margin-right: -70px;
                    width: 140px;
                    top: 50%;
                    padding: 8px;
                    border-radius: 8px;
                    z-order: 1000;
                    z-index: 555555555;
                    background: #fff;
                    text-align: center;
                    vertical-align: middle
                }

                #loader i {
                    display: block;
                    margin-bottom: 10px;
                    font-size: 50px
                }
            </style>
            <div id="loader"></div>

            <!-- Timeline start -->
            <div class="col-md-8 col-xs-12">
                <? if ($logged) if ($biz->owner !== $user->user_id && $letAddComment == 1) { ?>

                    <div class="panel panel-default bt-rd4">
                        <form class="ctgAjaxFrom" data-res="#reveiwBizpage" action="<?= $siteurl; ?>/model"
                              method="GET">
                            <div class="panel-body black">
                                <?
                                if ($biz->photo !== "") {
                                    $bizPhoto = $biz->photo;
                                } else {
                                    $bizPhoto = "usrUpload/text.png";
                                } ?>
                                <img
                                    src="http://citygramcdn.ir/bphoto/<?= urlencode(imageEncrypt($bizPhoto . ':" "::20')); ?>/90-90.ctg"
                                    class="pull-right" style="margin-left:20px;max-width:90px;width:23%;height:auto"/>
                                نظر شما راجع به <span class="red"><?= $biz->name; ?></span> چیه ؟<br/>
                                <div class="small">لطفا توضیح خلاصه و کاملی از کسب و کار بنویسید ...</div>
                                <input type="hidden" name="key"
                                       value="<?= encryptIt('bizPageAddReveiw_' . random_string(8)); ?>"/>
                                <input type="hidden" name="csrf_token" value="<?= getCSRFToken(); ?>"/>
                                <input type="hidden" name="bid" value="<?= $biz->bid; ?>"/>
                                <?
                                if ($logged) {
                                    $lastMonth = $now - (60 * 60 * 24 * 30);
                                    $query_checkLastMonthStar = $conn->prepare("SELECT * FROM `ctg_stars` WHERE `uid` = :uid AND bid = :bid AND date > :date ");
                                    $query_checkLastMonthStar->bindValue(":uid", $user->user_id);
                                    $query_checkLastMonthStar->bindValue(":bid", $biz->bid);
                                    $query_checkLastMonthStar->bindValue(":date", $lastMonth);
                                    $query_checkLastMonthStar->execute();
                                    $lastStarAdded = $query_checkLastMonthStar->rowCount();
                                } else {
                                    $lastStarAdded = 0;
                                }
                                if ($lastStarAdded == 0) {
                                    ?>
                                    <input id="input-22" value="" name="userReviewRating" type="number" class="rating"
                                           data-min="0" data-max="5" data-step="0.5" data-hoverChangeStars="false"
                                           data-rtl="1" data-size="xs" data-container-class="ltr mt5 text-right"
                                           data-glyphicon="0">
                                <? } ?>
                                <textarea name="userReveiw" class="mt5"
                                          style="width:100%;border-radius:3px;border:1px solid #ccc;"></textarea>
                                <div id="reveiwBizpage"></div>
                            </div>
                            <div class="panel-footer">
                                <button class="btn btn-danger mt5">ثبت نظر</button>
                                <span class="pull-left"><a href="<?= $siteurl; ?>/guide/review" class="mt5 ml5 ssmall">راهنمای
                                        نظردهی</a></span>
                            </div>
                        </form>
                    </div>

                <? } ?>
                <style>.timeline-centered .tooltip {
                        min-width: 80px;
                        float: none;
                        text-align: center !important
                    }</style>
                <div class="timeline-centered">
                    <?php
                    $index = 1;
                    foreach ($timelines as $tmline) {
                        if ($letAddComment !== 0) {
                            $letAddComment = '<hr style="margin:5px;background:#f5f5f5" />
										<form class="ctgAjaxFrom" action="' . $siteurl . '/model" data-res="#ctgAjaxFromResult' . $index . '" method="GET" >
											<input type="hidden" name="key" value="' . encryptIt('AddCommentIndex_' . random_string(8)) . '" />
											<input type="hidden" name="csrf_token" value="' . getCSRFToken() . '"/>
											<input type="hidden" name="bid" value="' . $biz->bid . '"/>
											<input type="hidden" name="kind" value="pic"/>
											<input type="hidden" name="valID" value="' . $tmline['bid'] . '"/>
											<div class="pull-right mainpage-reveiw" style="width:100%">
												<a href="' . $siteurl . '/page/report/ph-' . $tmline['bid'] . '" target="_blank" title="گزارش" class="pull-left redhover"><i class="fa fa-flag" ></i></a>
												<input class="notinput" type="radio" value="1" id="reveiw' . $index . '1" name="doreviw" />
												<label for="reveiw' . $index . '1">
													<span class="btn btn-dodowhite btn-sm"><i class=" fa fa-leaf middle ml5"></i><span class="hide600">مفید</span></span>
												</label>
												<input class="notinput" type="radio" value="2" id="reveiw' . $index . '2" name="doreviw" />
												<label for="reveiw' . $index . '2">
													<span class="btn btn-dodowhite btn-sm"><i class="fa fa-smile-o middle ml5"></i><span class="hide600">جالب</span></span>
												</label>
												<input class="notinput" type="radio" value="3" id="reveiw' . $index . '3" name="doreviw" />
												<label for="reveiw' . $index . '3">
													<span class="btn btn-dodowhite btn-sm"><i class="fa fa-check middle ml5"></i><span class="hide600">عالی</span></span>
												</label>
												<input class="notinput" type="radio" value="4" id="reveiw' . $index . '4" name="doreviw" />
												<label for="reveiw' . $index . '4">
													<span class="btn btn-dodowhite btn-sm"><i class="fa fa-bomb middle ml5"></i><span class="hide600">فوق العاده</span></span>
												</label>
												<div id="ctgAjaxFromResult' . $index . '" class="mr5 inline"></div>
											</div>
											<textarea name="reviewBox" class="reviewBox mt5" data-hddd="btnToAddComment' . $index . '" style="width:100%;margin:0;padding:0;height:50px;border-radius:3px;border:1px solid #ccc;"></textarea>
											<div id="btnToAddComment' . $index . '" style="display:none">
												<a class="cancelReview pull-left ssmall" >لغو نظردهی</a>
												<button class="btn btn-info btn-sm ssmall" style="padding:3px 6px" >ثبت نظر</button>
											</div>
										</form>';
                        } else {
                            $letAddComment = "";
                        }
                        $index++;
                        if ($biz !== false) {
                            if ($biz->primkey == "") $bizPrimary = $biz->city . $biz->bid; else $bizPrimary = $biz->primkey;
                        }

                        // GET USER DATA
                        if ($tmline['type'] !== "post" || $tmline['type'] !== "download" || $tmline['type'] !== "delivery") {
                            $usr = userdata($tmline['uid'], 'realname , user_id , avatar');
                        }


                        if ($tmline['type'] == "comment") {
                            $fa = "fa-comment";
                            $faClass = "bg-color1";
                            $actionName = "&nbsp;درج نظـر";
                            $tmtext = " در <a href='" . $siteurl . "/@" . $bizPrimary . "' title='" . $biz->name . "'>" . $biz->name . "</a> نظر داد ...";
                            $tmdata = '<p class="mt15"><i class="fa ccc fa-comment ml5" ></i> ' . html_entity_decode($tmline['data']) . '</p>' . $letAddComment;
                        } else if ($tmline['type'] == "like") {
                            $fa = "fa-thumbs-up fa-flip-horizontal";
                            $faClass = "bg-color2";
                            $actionName = "پسند کردن";
                            $tmtext = " ، <a href='" . $siteurl . "/@" . $bizPrimary . "' title='" . $biz->name . "'>" . $biz->name . "</a> را لایک کرد ";
                            $tmdata = '';
                        } else if ($tmline['type'] == "photo") {
                            $fa = "fa-camera";
                            $faClass = "bg-color5";
                            $actionName = "درج عکس";
                            $tmtext = " یک عکس جدید در <a href='" . $siteurl . "/@" . $bizPrimary . "' title='" . $biz->name . "'>" . $biz->name . "</a> پست کرد .";
                            $tmdata = '<img src="http://citygramcdn.ir/bphoto/' . urlencode(imageEncrypt($tmline["data"] . ":::18:1")) . '/.ctg" alt="' . $biz->name . '" title="' . $biz->name . '" class="img-responsive img-rounded mt5" />' . $letAddComment;
                        } else if ($tmline['type'] == "reservation") {
                            $fa = "fa-user";
                            $faClass = "bg-color6";
                            $actionName = "رزرو آنلاین";
                            $tmtext = "رزروی در <a href='" . $siteurl . "/@" . $bizPrimary . "' title='" . $biz->name . "'>" . $biz->name . "</a> ثبت کرد . ";
                            if ($tmline['data'] !== "0") {
                                $query_reserve = $conn->prepare("SELECT price,name FROM `ctg_reservation_menu` WHERE rvid = :rvid ");
                                $query_reserve->bindValue(':rvid', $tmline['data']);
                                $query_reserve->execute();
                                $rsv = $query_reserve->fetchObject();
                                $tmdata = '<p class="mt15"><i class="fa fa-usd ccc ml5"></i>  سفارش منوی ' . $rsv->name . ' به قیمت : ' . number_format($rsv->price) . ' تومان .</p>';
                            } else {
                                $tmdata = '<p class="mt15"><i class="fa fa-usd ccc ml5"></i>  سفارش عمومی ثبت کرد .</p>';
                            }

                        } else if ($tmline['type'] == "follow") {
                            $fa = "fa-reply";
                            $faClass = "bg-color7";
                            $actionName = "دنبال کردن";
                            $tmtext = " اکنون رخدادهای " . $biz->name . " را دنبال می کند .";
                            $tmdata = "";
                        } else if ($tmline['type'] == "compliment") {
                            $fa = "fa-bookmark";
                            $faClass = "bg-color3";
                            $actionName = "کسب مدال";
                            $query_complimt = $conn->prepare("SELECT name FROM `ctg_compliments_cats` WHERE `ccid` = :cmpid ");
                            $query_complimt->bindValue(':cmpid', $tmline['bid']);
                            $query_complimt->execute();
                            $comp = $query_complimt->fetchObject();
                            $tmtext = $comp->name . " را به " . $puser->realname . " اعطا کرد .";
                            $tmdata = '<p class="mt15"><i class="fa fa-star gold" style="margin-top:2px"></i> ' . $tmline['data'] . '</p>';
                        } else if ($tmline['type'] == "download") {
                            $fa = "fa-cloud-download";
                            $faClass = "bg-color3";
                            $actionName = "محصول دانلودی";
                            $tmtext = "محصول دیجیتال جدیدی به " . $biz->name . " اضافه شد.";
                            $tmdata = '<p class="mt15"><i class="fa fa-cloud-download green" style="margin-top:2px"></i> ' . $tmline['data'] . '</p>';
                        } else if ($tmline['type'] == "post") {
                            $fa = "fa-pencil";
                            $faClass = "bg-color7";
                            $actionName = "محتوا";
                            $tmtext = "پست جدیدی در " . $biz->name . " درج شد.";
                            $tmdata = '<p class="mt15"><i class="fa fa-pencil" style="margin-top:2px"></i> ' . $tmline['data'] . '</p>';
                        } else if ($tmline['type'] == "delivery") {
                            $fa = "fa-shopping-bag";
                            $faClass = "bg-color2";
                            $actionName = "محصول فیزیکی";
                            $tmtext = "محصول جدیدی به " . $biz->name . " اضافه شد.";
                            $tmdata = '<p class="mt15"><i class="fa fa-shoping-bag green" style="margin-top:2px"></i> ' . $tmline['data'] . '</p>';
                        }


                        // Create Avatar
                        if ($usr->avatar == NULL) $tavatar = "avatars/default.png";
                        else $tavatar = $usr->avatar;
                        ?>
                        <article class="timeline-entry left-aligned">
                            <div class="timeline-entry-inner">
                                <div title="<?= $actionName; ?>" rel="tooltip" class="timeline-icon <?= $faClass; ?>">
                                    <i class="fa <?= $fa; ?>"></i>
                                </div>

                                <div class="timeline-label">
                                    <span class="pull-left ssmall" title="<?= jdate('d F Y', $tmline['date']); ?>"
                                          rel="tooltip"><i
                                            class="fa fa-calendar ml5"></i> <?= how_datas_dif($tmline['date']); ?></span>
                                    <img
                                        src="http://citygramcdn.ir/avatar/<?= urlencode(imageEncrypt($tavatar)); ?>/20.ctg"
                                        class="ml5" style="width:20px;height:20px;border-radius:2px;"/>
                                    <h2 style="font-size:15px"><a
                                            href="<?= $siteurl; ?>/profile/<?= $usr->user_id; ?>/<?= str_replace(" ", "-", $usr->realname); ?>"
                                            title="<?= $usr->realname; ?>"><?= $usr->realname; ?></a> <span
                                            class="small"><?= $tmtext; ?></span></h2>
                                    <?= $tmdata !== "" ? "<br/>" . $tmdata : ""; ?>
                                </div>
                            </div>
                        </article>
                    <? } ?>

                    <div id="LoadMoreResult">
                    </div>
                    <?
                    $thispageScript .= '<script>
							var bee = 0;
							var price = 0;
							$(document).on("change", "#kindOfReserve" , function() {
								bee = $(this).find(":selected").data("getgrice");
								price = $(this).find(":selected").data("price");
								if(bee > 0 ){
									alert("مدیر کسب و کار برای رزرو این منو %"+bee+" مبلغ را به عنوان بیانه دریافت میکند .");
								}
							});
							$(document).on("click", "#DoReserve" , function() {
								var count = $("select[name=countOfReserve]").val();
								if(bee > 0 ){
									alert("برای رزرو "+count+" عدد محصول مبلغ "+((count*price*bee)/100)+" تومان را به عنوان بیانه می پردازید ؟");
								}
							});
							
							var e=0;$(document).on("click","#bizPageLoadMore",function(t){e++,window.Citygram.ajaxCall(siteurl+"/load/?load=bizPageLoadMore&bid=' . $biz->bid . '&page="+e,"LoadMoreResult",1)})</script>';
                    ?>

                    <article class="timeline-entry left-aligned mt20">
                        <div class="timeline-entry-inner">
                            <div class="timeline-icon" id="bizPageLoadMore" rel="tooltip" title="لــود بیشتر"
                                 style="margin:2px -2px 0 0 ">
                                <i class="fa fa-ellipsis-h"></i>
                            </div>
                            <div class="pull-left">...</div>
                        </div>
                    </article>

                    <article class="timeline-entry left-aligned begin" style="margin-top:40px">
                        <div class="timeline-entry-inner">
                            <div class="timeline-icon bg-color4" rel="tooltip" title="عضــو شـد"
                                 style="margin:2px -2px 0 0 ">
                                <i class="fa fa-user-plus"></i>
                            </div>

                            <div class="timeline-label">
                                <span class="pull-left ssmall" title="<?= jdate('d F Y', strtotime($biz->adddate)); ?>"
                                      rel="tooltip"><i
                                        class="fa fa-calendar ml5"></i> <?= how_datas_dif($biz->adddate); ?></span>
                                <img
                                    src="http://citygramcdn.ir/avatar/<?= urlencode(imageEncrypt($biz->photo)); ?>/20.ctg"
                                    class="ml5" style="width:20px;height:20px;border-radius:2px;"/>
                                <?= $biz->name; ?><span> به سیتی گرام پیوست ...</span>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>


    <!-- Reservation -->
    <div class="modal fade" id="onlineReservation" tabindex="-1" role="dialog" aria-labelledby="onlineReservation"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 style="margin-bottom:-6px;"><i class="fa fa-shopping-bag middle ml5"></i> رزرو آنلاین</h4>
                </div>
                <div class="modal-body">
                    <form id="ctgAjaxFrom" action="<?= $siteurl; ?>/model" method="GET">
                        <input type="hidden" name="key"
                               value="<?= encryptIt('bizPageReservation_' . random_string(8)); ?>"/>
                        <input type="hidden" name="csrf_token" value="<?= getCSRFToken(); ?>"/>
                        <input type="hidden" name="bid" value="<?= $biz->bid; ?>"/>
                        منوی رزرو :
                        <select name="kindOfReserve" id="kindOfReserve" class="noselect mr10" style="width:20%">
                            <option value="general" data-price="0" data-getgrice="0">عمومی</option>
                            <?php
                            $queryReserveMenu = $conn->prepare("SELECT * FROM `ctg_reservation_menu` WHERE `bid` = :bid ");
                            $queryReserveMenu->bindValue(':bid', $biz->bid);
                            $queryReserveMenu->execute();
                            $reservationMenus = $queryReserveMenu->fetchAll();
                            foreach ($reservationMenus as $reservationMenu) { ?>
                                <option data-price="<?= $reservationMenu['price']; ?>"
                                        data-getgrice="<?= $reservationMenu['getPrice']; ?>"
                                        value="<?= $reservationMenu['rvid']; ?>"><?= $reservationMenu['name']; ?></option>
                            <? } ?>
                        </select>
                        تعداد رزرو :
                        <select name="countOfReserve" class="noselect mr10" style="width:20%">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                        <hr/>
                        توضیح :
                        <textarea name="reserveDescription" class="mt5"
                                  style="width:100%;border-radius:3px;border:1px solid #ccc;"></textarea>
                        <div id="ctgAjaxFromResult"></div>

                        <span class="red">توجه شود که اطلاعات تماس شما به صاحب کسب و کار فرستاده میشود .</span>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="DoReserve" class="btn3d btn-info mt5">رزرو کردن</button>
                    <button type="button" class="btn btn-default" style="float:left;" data-dismiss="modal">بستن پنجره <i
                            class="fa fa-power-off" style="font-size:13px;"></i></button>
                </div>
            </div>
        </div>
    </div>
<? } ?>
<?php
// $thispageScript = "";
include_once("template/footer.php"); ?>
