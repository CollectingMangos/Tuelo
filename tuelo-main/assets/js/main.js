'use strict';

// ---- Sidebar accordion (category filter) ----
const accordionBtns = document.querySelectorAll('[data-accordion-btn]');
const accordions = document.querySelectorAll('[data-accordion]');

for (let i = 0; i < accordionBtns.length; i++) {
  accordionBtns[i].addEventListener('click', function () {
    const isAlreadyOpen = this.nextElementSibling.classList.contains('active');

    // close all
    for (let j = 0; j < accordions.length; j++) {
      accordions[j].classList.remove('active');
      accordionBtns[j].classList.remove('active');
    }

    // open clicked one if it was closed
    if (!isAlreadyOpen) {
      this.nextElementSibling.classList.add('active');
      this.classList.add('active');
    }
  });
}

// ---- Banner auto-scroll ----
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