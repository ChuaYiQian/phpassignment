window.addEventListener('load', function () {
    const slider = document.querySelector('.slider');
    const slides = Array.from(document.querySelectorAll('.slide'));
    const slideWidth = slides[0].offsetWidth;

    window.moveSlide = function (step) {
        if (step === 1) {
            slider.style.transition = "transform 0.3s ease-in-out";
            slider.style.transform = `translateX(-${slideWidth}px)`;

            setTimeout(() => {
                const firstSlide = slider.firstElementChild;
                slider.appendChild(firstSlide);
                slider.style.transition = "none";
                slider.style.transform = "translateX(0)";
            }, 500);
        } else {
            const lastSlide = slider.lastElementChild;
            slider.insertBefore(lastSlide, slider.firstElementChild);
            slider.style.transition = "none";
            slider.style.transform = `translateX(-${slideWidth}px)`;

            setTimeout(() => {
                slider.style.transition = "transform 0.3s ease-in-out";
                slider.style.transform = "translateX(0)";
            }, 50);
        }
    };
});

$(document).ready(function () {
    const popup = $('.popup-message');
    if (popup.length) {
        popup.addClass('show').fadeIn(300).delay(3000).fadeOut(500);
    }
});


