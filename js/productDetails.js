let currentIndex = 0;

$(document).ready(function () {
    const $slides = $('.detail-slide-image');
    const $slider = $('#detailSlider');

    function moveSlide(index) {
        const slideWidth = $slides.first().outerWidth();
        $slider.css('transform', `translateX(-${index * slideWidth}px)`);
    }

    $('.slider-btn.prev').on('click', function () {
        currentIndex--;
        if (currentIndex < 0) currentIndex = $slides.length - 1;
        moveSlide(currentIndex);
    });

    $('.slider-btn.next').on('click', function () {
        currentIndex++;
        if (currentIndex >= $slides.length) currentIndex = 0;
        moveSlide(currentIndex);
    });
});
