(function ($) {
    $(document).ready(function () {
        function WpmfWindowCenter(url, title, w, h) {
            // Fixes dual-screen position
            var dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : screen.left;
            var dualScreenTop = window.screenTop !== undefined ? window.screenTop : screen.top;

            var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
            var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

            var left = ((width / 2) - (w / 2)) + dualScreenLeft;
            var top = ((height / 2) - (h / 2)) + dualScreenTop;
            var newWindow = window.open(url, title, 'scrollbars=yes, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
        }

        $('.wpmf_odv_video,.wpmf_dbx_video').on('click',function(e){
            var $this = $(this);
            e.preventDefault();
            var src = $(this).data('src');
            var width_window = $(window).width();
            var width = Math.ceil(width_window*80/100);
            var height = Math.ceil(width*0.5);
            WpmfWindowCenter(src, "OdvWindow", width, height);

        });

        $('.wpmf_odv_video').css({'position':'relative'}).append('<img class="visible" style="right:20px;bottom: 20px;position: absolute;box-shadow: none" title="" src="'+ wpmfaddonlang.wpmf_images_path +'/video.png" alt="" />');
    });
})(jQuery);
