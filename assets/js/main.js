/**
 * PORTAL MAURINSOFT - MAIN JAVASCRIPT
 * Interatividades, navegação mobile, abas e galerias
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Mobile Menu Toggle
  const hamburger = document.querySelector('.hamburger');
  const navMenu = document.querySelector('.nav-menu');

  if (hamburger && navMenu) {
    hamburger.addEventListener('click', () => {
      navMenu.classList.toggle('active');
      const expanded = hamburger.getAttribute('aria-expanded') === 'true' || false;
      hamburger.setAttribute('aria-expanded', !expanded);
    });

    // Close menu when clicking on a link
    navMenu.querySelectorAll('.nav-link:not(.nav-dropdown-btn)').forEach(link => {
      link.addEventListener('click', () => {
        navMenu.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // 2. Sticky Navbar on Scroll
  const header = document.querySelector('.site-header');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 40) {
      header?.classList.add('scrolled');
    } else {
      header?.classList.remove('scrolled');
    }
  });

  // 3. Tab System (used in product & service pages)
  const tabBtns = document.querySelectorAll('.tab-btn');
  const tabPanes = document.querySelectorAll('.tab-pane');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-tab');

      tabBtns.forEach(b => b.classList.remove('active'));
      tabPanes.forEach(p => p.classList.remove('active'));

      btn.classList.add('active');
      const targetPane = document.getElementById(targetId);
      if (targetPane) {
        targetPane.classList.add('active');
      }
    });
  });

  // 4. Product Gallery Thumbnail Switcher
  const mainImage = document.getElementById('main-gallery-img');
  const thumbs = document.querySelectorAll('.thumb-item');

  thumbs.forEach(thumb => {
    thumb.addEventListener('click', () => {
      thumbs.forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
      const newSrc = thumb.getAttribute('data-src');
      if (mainImage && newSrc) {
        mainImage.style.opacity = '0.5';
        setTimeout(() => {
          mainImage.src = newSrc;
          mainImage.style.opacity = '1';
        }, 150);
      }
    });
  });

  // 5. Contact & Quote Form Handling (Simulated feedback with WhatsApp redirection option)
  const contactForm = document.getElementById('portal-contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const submitBtn = contactForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;

      submitBtn.disabled = true;
      submitBtn.innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin">
          <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
          <path d="M12 2a10 10 0 0 1 10 10"></path>
        </svg> Enviando...
      `;

      // Read form data
      const name = document.getElementById('form-name')?.value || '';
      const email = document.getElementById('form-email')?.value || '';
      const interest = document.getElementById('form-interest')?.value || 'Geral';
      const message = document.getElementById('form-message')?.value || '';

      setTimeout(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;

        // Display success banner
        let successAlert = document.getElementById('form-success-alert');
        if (!successAlert) {
          successAlert = document.createElement('div');
          successAlert.id = 'form-success-alert';
          successAlert.style.cssText = `
            margin-top: 1.5rem;
            padding: 1.25rem;
            border-radius: 10px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid #10b981;
            color: #d1fae5;
            font-size: 0.95rem;
            line-height: 1.5;
          `;
          contactForm.appendChild(successAlert);
        }

        successAlert.innerHTML = `
          <strong>Obrigado pelo contato, ${name}!</strong><br>
          Sua mensagem sobre <em>${interest}</em> foi registrada com sucesso. Nossos engenheiros entrarão em contato em até 24 horas.<br>
          <div style="margin-top: 10px;">
            <a href="https://wa.me/5516981434112?text=Ol%C3%A1%20Maurinsoft,%20meu%20nome%20%C3%A9%20${encodeURIComponent(name)}.%20Gostaria%20de%20informa%C3%A7%C3%B5es%20sobre%20${encodeURIComponent(interest)}:%20${encodeURIComponent(message)}" 
               target="_blank" 
               class="btn btn-primary btn-sm" 
               style="display: inline-flex; margin-top: 5px;">
               Conversar agora no WhatsApp
            </a>
          </div>
        `;

        contactForm.reset();
      }, 1000);
    });
  }

  // 6. Pre-select interest dropdown from URL query parameters (e.g. ?interesse=hemacias)
  const urlParams = new URLSearchParams(window.location.search);
  const interestParam = urlParams.get('interesse');
  const interestSelect = document.getElementById('form-interest');
  if (interestSelect && interestParam) {
    const paramLower = interestParam.toLowerCase();
    for (let i = 0; i < interestSelect.options.length; i++) {
      const optVal = interestSelect.options[i].value.toLowerCase();
      if (optVal.includes(paramLower) || (paramLower === 'hemacias' && optVal.includes('hemácias'))) {
        interestSelect.selectedIndex = i;
        break;
      }
    }
  }
});
