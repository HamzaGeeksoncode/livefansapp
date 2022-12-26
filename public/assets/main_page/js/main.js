function isWebp() {
  function testWebp(cb) {
    let webP = new Image();
    webP.onload = webP.onerror = function () {
      cb(webP.height == 2);
    };
    webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
  }

  testWebp(function (support) {
    let className = support === true ? 'webp' : 'no-webp';
    document.documentElement.classList.add(className);
  });
}
isWebp();

document.addEventListener('DOMContentLoaded', () => {
  const mainEvents = {
    monetizationSlider: () => {
      const swiper = new Swiper('.monetization .monetization__slider', {
        loop: true,
        slidesPerView: 1,
        // If we need pagination
        pagination: {
          el: '.monetization .swiper-pagination',
          clickable: true,
        },
        // Navigation arrows
        navigation: {
          nextEl: '.monetization .swiper-button-next',
          prevEl: '.monetization .swiper-button-prev',
        },
      });
    },
    creatorsSlider: () => {
      const swiper = new Swiper('.creators .creators__slider', {
        loop: true,
        slidesPerView: 3,
        spaceBetween: 10,
        centeredSlides: true,

        // If we need pagination
        pagination: {
          el: '.creators .swiper-pagination',
          clickable: true,
        },
        // Navigation arrows
        navigation: {
          nextEl: '.creators .swiper-button-next',
          prevEl: '.creators .swiper-button-prev',
        },
        breakpoints: {
          // when window width is >= 320px
          320: {
            slidesPerView: 1,
            spaceBetween: 0,
          },
          992: {
            slidesPerView: 2,
            spaceBetween: 10,
          },
          1350: {
            slidesPerView: 3,
            spaceBetween: 10,
          },
        },
      });
      swiper.on('slideChange', function () {
        console.log('slide changed', this);
        console.log('this.realIndex', this.realIndex);
        if (this.realIndex === 2) {
          console.log('yeyeye');
        }
      });
    },
    handleOpenSearch: () => {
      $('.header__top .header__search_icon').on('click', (e) => {
        const $this = $(e.currentTarget);
        $this.closest('.header__search').toggleClass('open');
      });
    },
    handleOpenQuestionItem: () => {
      $('.questions .questions__item_title').on('click', (e) => {
        const $this = $(e.currentTarget);
        if ($this.closest('.questions__item').hasClass('active')) {
          $this.closest('.questions__item').removeClass('active');
          $this.next('.questions__item_faq').slideUp();
        } else {
          $('.questions .questions__item').removeClass('active');
          $('.questions .questions__item .questions__item_faq').slideUp();
          $this.closest('.questions__item').addClass('active');
          $this.next('.questions__item_faq').slideDown();
        }
      });
      $(document).on('click', (e) => {
        const $target = $(e.target);
        if (!$target.closest('.questions__item').length) {
          $('.questions .questions__item').removeClass('active');
          $('.questions .questions__item .questions__item_faq').slideUp();
        }
      });
    },
    handleOpenDropdownForm: () => {
      $('.credit .dropdown__btn').on('click', (e) => {
        const $this = $(e.currentTarget);
        $this.closest('.form__group').toggleClass('open');
      });
    },
    handleChooseCurrentDropdownItem: () => {
      $('.credit .dropdown__item').on('click', (e) => {
        const $this = $(e.currentTarget);
        $this.closest('.form__group').removeClass('open');
      });
    },
    handleOpenMobileMenu: () => {
      $('.header__top .menu_btn').on('click', (e) => {
        const $this = $(e.currentTarget);
        $this.closest('.header').toggleClass('isOpen');
      });
    },
    rangeSlider: () => {
      const following = document.getElementById('quantity__following');
      const cost = document.getElementById('quantity__cost');
      let amount = 1;
      let followingAmount = 0;
      let costAmount = 0;
      const followingSlider = noUiSlider.create(following, {
        range: {
          min: 1,
          max: 100000,
        },
        step: 50,
        start: 1,
        // Move handle on tap, bars are draggable
        behaviour: 'tap-drag',
        tooltips: true,
        format: wNumb({
          decimals: 0,
          thousand: '',
          postfix: '<br><span>seguidores</span>',
        }),
        // Show a scale with the slider
      });
      followingSlider.on('update', function (values, handle, unencoded) {
        const value = +unencoded[handle].toFixed(0);
        followingAmount = value;
        amount = followingAmount * costAmount;
        $('.quantity .quantity__amount .value').text(amount.toFixed(2));
      });
      const costSlider = noUiSlider.create(cost, {
        range: {
          min: 5,
          max: 10000,
        },
        step: 55,
        start: 5,
        // Move handle on tap, bars are draggable
        behaviour: 'tap-drag',
        tooltips: true,
        // format: wNumb({
        //   decimals: 0,
        //   thousand: '',
        //   postfix: '<br><span>valor da assinatura</span>',
        //   // suffix: 'R$',
        // }),
        // Show a scale with the slider
        format: {
          from: function (value) {
            return Number(value.replace(' ' + 'R$'));
          },
          to: function (value) {
            return 'R$' + ' ' + value.toFixed(2) + '<br><span>valor da assinatura</span>';
          },
        },
      });
      costSlider.on('update', function (values, handle, unencoded) {
        const value = +unencoded[handle].toFixed(0);
        costAmount = value;
        amount = followingAmount * costAmount;
        $('.quantity .quantity__amount .value').text(amount.toFixed(2));
      });
    },
    securitySectionAnim: () => {
      particlesJS('security', {
        particles: {
          number: {
            value: 100,
            density: {
              enable: true,
              value_area: 1000,
            },
          },
          color: {
            value: ['#e2e2e2', '#bdbdbd', '#7f7c7c', '#525252'],
          },

          shape: {
            type: 'circle',
            stroke: {
              width: 0,
              color: '#fff',
            },
            polygon: {
              nb_sides: 5,
            },
            image: {
              src: 'img/github.svg',
              width: 100,
              height: 100,
            },
          },
          opacity: {
            value: 0.6,
            random: false,
            anim: {
              enable: false,
              speed: 1,
              opacity_min: 0.1,
              sync: false,
            },
          },
          size: {
            value: 2,
            random: true,
            anim: {
              enable: false,
              speed: 40,
              size_min: 0.1,
              sync: false,
            },
          },
          line_linked: {
            enable: true,
            distance: 120,
            color: '#ffffff',
            opacity: 0.4,
            width: 1,
          },
        },
        interactivity: {
          detect_on: 'canvas',
          events: {
            onhover: {
              enable: true,
              mode: 'grab',
            },
            onclick: {
              enable: false,
            },
            resize: true,
          },
          modes: {
            grab: {
              distance: 140,
              line_linked: {
                opacity: 1,
              },
            },
            bubble: {
              distance: 400,
              size: 40,
              duration: 2,
              opacity: 8,
              speed: 3,
            },
            repulse: {
              distance: 200,
              duration: 0.4,
            },
            push: {
              particles_nb: 4,
            },
            remove: {
              particles_nb: 2,
            },
          },
        },
        retina_detect: true,
      });
    },
    quantityRange: () => {},
  };

  mainEvents.monetizationSlider();
  mainEvents.creatorsSlider();
  mainEvents.handleOpenSearch();
  mainEvents.handleOpenQuestionItem();
  mainEvents.handleOpenDropdownForm();
  mainEvents.handleChooseCurrentDropdownItem();
  mainEvents.handleOpenMobileMenu();
  mainEvents.rangeSlider();
  mainEvents.securitySectionAnim();
});
