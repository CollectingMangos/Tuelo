'use strict';

/* Mobile navigation drawer */
const mobileMenuBtn = document.querySelector('[data-mobile-menu-btn]');
const mobileNav     = document.querySelector('[data-nav]');
const navCloseBtn   = document.querySelector('[data-nav-close]');
const overlay       = document.querySelector('[data-overlay]');

function openMobileNav() {
  mobileNav.classList.add('active');
  overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeMobileNav() {
  mobileNav.classList.remove('active');
  overlay.classList.remove('active');
  document.body.style.overflow = '';
}

if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMobileNav);
if (navCloseBtn)   navCloseBtn.addEventListener('click', closeMobileNav);
if (overlay)       overlay.addEventListener('click', closeMobileNav);

/* Accordion */
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
