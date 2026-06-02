'use strict';

const accordionBtns = document.querySelectorAll('[data-accordion-btn]');
const accordions = document.querySelectorAll('[data-accordion]');

for (let i = 0; i < accordionBtns.length; i++) {
  accordionBtns[i].addEventListener('click', function () {
    const isAlreadyOpen = this.nextElementSibling.classList.contains('active');

    for (let j = 0; j < accordions.length; j++) {
      accordions[j].classList.remove('active');
      accordionBtns[j].classList.remove('active');
    }

    if (!isAlreadyOpen) {
      this.nextElementSibling.classList.add('active');
      this.classList.add('active');
    }
  });
}

const sliderContainer = document.querySelector('.slider-container');
if (sliderContainer) {
  let currentSlide = 0;
  const slides = sliderContainer.querySelectorAll('.slider-item');
  const total = slides.length;

  if (total > 1) {
    setInterval(function () {
      currentSlide = (currentSlide + 1) % total;
      sliderContainer.scrollTo({
        left: slides[currentSlide].offsetLeft,
        behavior: 'smooth'
      });
    }, 4000);
  }
}
