<script scoped>


export default {
  name: 'EposLanding',
  data() {
    return {
      features: [
        {
          title: 'Inventory Management',
          description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt'
        },
        {
          title: 'Multi-Outlet Support',
          description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt'
        },
        {
          title: 'Smart Analytics',
          description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt'
        },
        {
          title: 'Real-Time Sales Reports',
          description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt'
        },
        {
          title: 'Role-Based Permissions',
          description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt'
        },
        {
          title: 'Offline Mode',
          description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt'
        }
      ],
      howItWorks: [
        {
          title: 'Register & Set Up',
          description: 'Sign up & verify your business.',
          image: 'path-to-register-image.jpg'
        },
        {
          title: 'Configure POS setup',
          description: 'Configure POS setup in minutes.',
          image: 'path-to-configure-image.jpg'
        },
        {
          title: 'Start selling instantly',
          description: 'Start selling instantly.',
          image: 'path-to-selling-image.jpg'
        }
      ],
      isAnnual: false,
      pricingPlans: [
        {
          name: 'Free Trial',
          monthlyPrice: 3,
          annualPrice: 3,
          features: [
            '14 days',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum'
          ]
        },
        {
          name: 'Pro Plan',
          monthlyPrice: 6,
          annualPrice: 6,
          features: [
            '1 Free Migration',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum'
          ],
          popular: false
        },
        {
          name: 'Enterprise',
          monthlyPrice: 8,
          annualPrice: 8,
          features: [
            '1 Free Migration',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum',
            'Lorem ipsum'
          ]
        }
      ],
      testimonials: [
        {
          name: 'Lorem Ipsum',
          role: 'Has Been Dummy Text',
          rating: 5,
          text: 'Lorem Ipsum Is Simply Dummy Text Of The Printing And Typesetting Industry. Lorem Ipsum Has Been The Industry\'s Standard Dummy Text Ever Since The 1500s., Lorem Ipsum Is Simply Dummy Text Of The Printing And Typesetting Industry.'
        },
        {
          name: 'Lorem Ipsum',
          role: 'Has Been Dummy Text',
          rating: 5,
          text: 'Lorem Ipsum Is Simply Dummy Text Of The Printing And Typesetting Industry. Lorem Ipsum Has Been The Industry\'s Standard Dummy Text Ever Since The 1500s., Lorem Ipsum Is Simply Dummy Text Of The Printing And Typesetting Industry.'
        },
        {
          name: 'Lorem Ipsum',
          role: 'Has Been Dummy Text',
          rating: 5,
          text: 'Lorem Ipsum Is Simply Dummy Text Of The Printing And Typesetting Industry. Lorem Ipsum Has Been The Industry\'s Standard Dummy Text Ever Since The 1500s., Lorem Ipsum Is Simply Dummy Text Of The Printing And Typesetting Industry.'
        },
        {
          name: 'Lorem Ipsum',
          role: 'Has Been Dummy Text',
          rating: 5,
          text: 'Lorem Ipsum Is Simply Dummy Text Of The Printing And Typesetting Industry. Lorem Ipsum Has Been The Industry\'s Standard Dummy Text Ever Since The 1500s., Lorem Ipsum Is Simply Dummy Text Of The Printing And Typesetting Industry.'
        }
      ],
      faqs: [
        {
          question: 'How long until we deliver your first blog post?',
          answer: 'Really they joe country she unable her sister. Feet you off for his for joy. Among she are tears her. hair Now. & Really they joe country she unable her sister among wammy.',
          open: false
        },
        {
          question: 'How long until we deliver your first blog post?',
          answer: 'Really they joe country she unable her sister. Feet you off for his for joy. Among she are tears her. hair Now. & Really they joe country she unable her sister among wammy.',
          open: false
        },
        {
          question: 'How long until we deliver your first blog post?',
          answer: 'Really they joe country she unable her sister. Feet you off for his for joy. Among she are tears her. hair Now. & Really they joe country she unable her sister among wammy.',
          open: false
        },
        {
          question: 'How long until we deliver your first blog post?',
          answer: 'Really they joe country she unable her sister. Feet you off for his for joy. Among she are tears her. hair Now. & Really they joe country she unable her sister among wammy.',
          open: false
        },
        {
          question: 'How long until we deliver your first blog post?',
          answer: 'Really they joe country she unable her sister. Feet you off for his for joy. Among she are tears her. hair Now. & Really they joe country she unable her sister among wammy.',
          open: false
        },
        {
          question: 'How long until we deliver your first blog post?',
          answer: 'Really they joe country she unable her sister. Feet you off for his for joy. Among she are tears her. hair Now. & Really they joe country she unable her sister among wammy.',
          open: false
        }
      ],
      pages: [
        { id: "01", text: "POS Dashboard" },
        { id: "02", text: "Inventory Tracking" },
        { id: "03", text: "Expense & Accounting" },
        { id: "04", text: "Staff Roles & Permissions" },
        { id: "05", text: "Multi-Location Management" },
        { id: "06", text: "Payment Integrations" }
      ]
    }
  },
  methods: {
    scrollToSection(section) {
      // Add scroll logic
    },
    startFreeTrial() {
      // Add trial logic
    },
    watchDemo() {
      // Add demo logic
    },
    toggleFaq(index) {
      this.faqs[index].open = !this.faqs[index].open;
    }
  }
}
</script>

<template>
  <div class="epos-landing">
    <!-- Navigation -->
    <nav class="navbar bg-red navbar-expand-lg navbar-dark">
      <div class="container">
        <a class="navbar-brand fw-bold" href="#">EPOS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>

        <ul class="navbar-nav mx-auto">
          <li class="nav-item">
            <a class="nav-link" href="#home">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#features">Features</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#how-it-works">How It Work</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#pricing">Pricing Plans</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#faqs">FAQs</a>
          </li>
        </ul>
        <button class="btn btn-light btn-get-trial" @click="startFreeTrial">Get Free Trial</button>

      </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <h1 class="hero-title mb-4">
              Modern Cloud-Based EPOS<br>
              for Restaurants <span class="text-light-blue">& Retail.</span>
            </h1>
            <div class="d-flex gap-3">
              <button class="btn btn-primary btn-lg" @click="startFreeTrial">Start Free Trial</button>
              <button class="btn btn-outline-light btn-lg" @click="watchDemo">Watch Demo</button>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="hero-devices">
              <img src="/assets/img/hero-section-laptops.png" alt="Analytics Dashboard" class="device-right">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section py-5">
      <div class="container">
        <h2 class="text-center mb-5">Features of EPOS</h2>
        <div class="row g-4">
          <div v-for="(feature, index) in features" :key="index" class="col-md-6 col-lg-4">
            <div class="feature-card h-100">
              <h5 class="feature-title">{{ feature.title }}</h5>
              <p class="feature-description">{{ feature.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works-section py-5">
      <div class="container">
        <h2 class="text-center mb-5">How It Works</h2>
        <div class="row g-4">
          <div v-for="(step, index) in howItWorks" :key="index" class="col-md-4">
            <div class="work-card text-center">
              <div class="work-image mb-3">
                <img src="/assets/img/work-1.jpg" :alt="step.title" class="img-fluid rounded">
              </div>
              <h5 class="work-title mb-2">{{ step.title }}</h5>
              <p class="work-description">{{ step.description }}</p>
            </div>
          </div>
        </div>
        <div class="text-center mt-5">
          <button class="btn btn-primary btn-lg px-5">Get Start</button>
        </div>
      </div>
    </section>

    <!-- Pricing Plans Section -->
    <section id="pricing" class="pricing-section py-5">
      <div class="container">
        <h2 class="text-center mb-4">Pricing Plans</h2>

        <!-- Toggle Switch -->
        <div class="d-flex justify-content-center align-items-center mb-4">
          <span class="me-3" :class="{ 'text-muted': isAnnual }">Monthly</span>
          <div class="form-check form-switch">
            <input class="form-check-input pricing-toggle" type="checkbox" v-model="isAnnual" id="pricingToggle">
          </div>
          <span class="ms-3" :class="{ 'text-muted': !isAnnual }">Annually (2 Months Free)</span>
        </div>

        <!-- Benefits -->
        <div class="row g-3 mb-5">
          <div class="col-md-2">
            <div class="benefit-item text-center">
              <i class="bi bi-headset"></i>
              <small>24/7/365 Support</small>
            </div>
          </div>
          <div class="col-md-2">
            <div class="benefit-item text-center">
              <i class="bi bi-arrow-repeat"></i>
              <small>30 Day Money Back Guarantee</small>
            </div>
          </div>
          <div class="col-md-2">
            <div class="benefit-item text-center">
              <i class="bi bi-infinity"></i>
              <small>99.9% Uptime Guarantee</small>
            </div>
          </div>
          <div class="col-md-2">
            <div class="benefit-item text-center">
              <i class="bi bi-shield-check"></i>
              <small>Free Setup & No Hidden Fees</small>
            </div>
          </div>
          <div class="col-md-2">
            <div class="benefit-item text-center">
              <i class="bi bi-tag"></i>
              <small>Same Price Every Year</small>
            </div>
          </div>
          <div class="col-md-2">
            <div class="benefit-item text-center">
              <i class="bi bi-x-circle"></i>
              <small>No Contracts - Cancel Any Time</small>
            </div>
          </div>
        </div>

        <!-- Pricing Cards -->
        <div class="row g-4 justify-content-center">
          <div v-for="(plan, index) in pricingPlans" :key="index" class="col-lg-4 col-md-6">
            <div class="pricing-card" :class="{ 'popular': plan.popular }">
              <div class="card-header">
                <h4>{{ plan.name }}</h4>
                <div class="price-tag">
                  <span class="currency">$</span>
                  <span class="price">{{ isAnnual ? plan.annualPrice : plan.monthlyPrice }}</span>
                  <span class="period">/{{ isAnnual ? 'YEAR' : 'MO' }}</span>
                </div>
              </div>
              <div class="card-body">
                <ul class="feature-list">
                  <li v-for="(feature, idx) in plan.features" :key="idx">
                    <i class="bi bi-check-circle"></i>
                    <span>{{ feature }}</span>
                  </li>
                </ul>
                <button class="btn btn-primary w-100">Free Trial</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section py-5">
      <div class="container">
        <h2 class="text-center mb-5">Client Testimonials</h2>
        <div class="row g-4">
          <div v-for="(testimonial, index) in testimonials" :key="index" class="col-lg-3 col-md-6">
            <div class="testimonial-card">
              <div class="d-flex align-items-center mb-3">
                <div class="testimonial-avatar me-3">
                  <i class="bi bi-person-circle"></i>
                </div>
                <div>
                  <h6 class="mb-0">{{ testimonial.name }}</h6>
                  <small class="text-muted">{{ testimonial.role }}</small>
                </div>
              </div>
              <div class="rating mb-2">
                <i v-for="star in testimonial.rating" :key="star" class="bi bi-star-fill"></i>
              </div>
              <p class="testimonial-text">{{ testimonial.text }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section id="faqs" class="faq-section py-5">
      <div class="container">
        <h2 class="text-center mb-5">Frequently Ask Questions</h2>
        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="row g-3">
              <div v-for="(faq, index) in faqs" :key="index" class="col-md-6">
                <div class="faq-item" @click="toggleFaq(index)">
                  <div class="faq-question">
                    <span>{{ faq.question }}</span>
                    <i class="bi" :class="faq.open ? 'bi-dash' : 'bi-plus'"></i>
                  </div>
                  <div v-if="faq.open" class="faq-answer">
                    {{ faq.answer }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="text-center mt-5">
          <button class="btn btn-primary btn-lg px-5">Get Start</button>
        </div>
      </div>
    </section>


    <section id="frequent-pages" class="faq-section py-5">
      <div class="container">
        <h2 class="text-center mb-5">Frequently Pages</h2>
        <div class="row justify-content">
          <div class="col-md-2 col-sm-4 col-6 mb-4" v-for="page in pages" :key="page.id">
            <div class="circle-box text-center mx-auto">
              <div class="circle-number">{{ page.id }}</div>
              <p class="circle-text mb-0">{{ page.text }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="free-trial" class="py-5">
      <div class="container">
        <div class="row align-items-stretch shadow-lg">
          <!-- Left: Form Section -->
          <div class="col-md-6 bg-dark text-white p-5">
            <h3 class="text-primary fw-bold mb-4">Start Free <span class="text-white">Trial</span></h3>
            <form>
              <div class="row mb-3">
                <div class="col">
                  <input type="text" class="form-control" placeholder="First Name">
                </div>
                <div class="col">
                  <input type="text" class="form-control" placeholder="Last Name">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col">
                  <input type="email" class="form-control" placeholder="Email">
                </div>
                <div class="col">
                  <input type="password" class="form-control" placeholder="Password">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col">
                  <input type="text" class="form-control" placeholder="Country">
                </div>
                <div class="col">
                  <input type="text" class="form-control" placeholder="Currency">
                </div>
              </div>
              <div class="mb-3">
                <input type="text" class="form-control" placeholder="Business Type: (Restaurant, Café, Retail, etc.)">
              </div>
              <button type="submit" class="btn btn-primary mt-4">Send Message</button>
            </form>
          </div>

          <!-- Right: Info Section -->
          <div class="col-md-6 bg-primary text-white p-5 d-flex flex-column justify-content-center">
            <h3 class="fw-bold">Lorem ipsum dolor <br> <span class="text-light">sit amet</span></h3>
            <h6 class="text-uppercase text-light mt-3">Think We Do Next.</h6>
            <p class="mt-4">Our team will contact you within one business day.<br>
              We will conduct an initial discussion to understand your requirements.<br>
              Our analysts and developers will assess the project scope.<br>
              We will collaboratively propose the next steps.<br>
              All information exchanges are secure and confidential.
            </p>
          </div>
        </div>
      </div>

      <!-- Footer Section -->
      <footer class="footer bg-dark text-white py-5 mt-5">
        <div class="container">
          <div class="row">
            <div class="col-md-3">
              <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</p>
            </div>
            <div class="col-md-3">
              <h6 class="fw-bold">Menu</h6>
              <ul class="list-unstyled mt-3">
                <li><a href="#" class="text-white text-decoration-none">Home</a></li>
                <li><a href="#" class="text-white text-decoration-none">Features</a></li>
                <li><a href="#" class="text-white text-decoration-none">Pricing</a></li>
                <li><a href="#" class="text-white text-decoration-none">Faqs</a></li>
                <li><a href="#" class="text-white text-decoration-none">Contact</a></li>
                <li><a href="#" class="text-white text-decoration-none">Privacy Policy</a></li>
              </ul>
            </div>
            <div class="col-md-3">
              <h6 class="fw-bold">Services</h6>
              <ul class="list-unstyled mt-3">
                <li>POS Dashboard</li>
                <li>Inventory Tracking</li>
                <li>Expense & Accounting</li>
                <li>Staff Roles & Permissions</li>
                <li>Multi-Location Management</li>
              </ul>
            </div>
            <div class="col-md-3">
              <h6 class="fw-bold">Contact Us</h6>
              <ul class="list-unstyled mt-3">
                <li><i class="fa fa-envelope me-2"></i> loremipsum@gmail.cm</li>
                <li><i class="fa fa-phone me-2"></i> +2-646230055-52</li>
                <li><i class="fab fa-instagram me-2"></i> <i class="fab fa-facebook"></i></li>
              </ul>
            </div>
          </div>
          <div class="text-center border-top mt-4 pt-3 small">
            Copyright Note: © 2025 All Rights Reserved.
          </div>
        </div>
      </footer>
    </section>


  </div>
</template>

<style scoped>
:root {
  --primary-blue: #4262B3;
  --secondary-blue: #4262B3;
  --light-blue: #4262B3;
  --text-light: #E8EAFF;
}
#free-trial {
  position: relative;
  z-index: 2;
  margin-top: -100px; /* adjust value depending on your design */
}

#frequent-pages {
  position: relative;
  z-index: 1;
}

#frequent-pages {
  background-color: #F0F1F2;
  border-bottom-left-radius: 15%;
  border-bottom-right-radius: 15%;
}

.box {
  background-color: pink;
}

.epos-landing {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  overflow-x: hidden;
}



/* Navigation */
.navbar {
  background: transparent;
  padding: 20px 0;
  position: absolute;
  width: 100%;
  z-index: 10;
}

.navbar-brand {
  font-size: 24px;
  color: white !important;
}

.navbar-nav .nav-link {
  color: white !important;
  font-weight: 500;
  margin: 0 15px;
  transition: opacity 0.3s;
}

.navbar-nav .nav-link:hover {
  opacity: 0.8;
}

.btn-get-trial {
  background: #2C50AA;
  color: white;
  font-weight: 600;
  padding: 8px 24px;
  border-radius: 6px;
  border: none;
}

.btn-get-trial:hover {
  background: rgba(255, 255, 255, 0.9);
  color: var(--primary-blue);
}

/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, #4A5FBF 0%, #5B6FD8 50%, #7B8EE8 100%);
  min-height: 600px;
  position: relative;
  overflow: hidden;
  padding: 120px 0 100px;
}

.hero-section::before {
  content: '';
  position: absolute;
  width: 150%;
  height: 150%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
  background-size: 50px 50px;
  top: -25%;
  left: -25%;
  animation: gridMove 20s linear infinite;
}

@keyframes gridMove {
  0% {
    transform: translate(0, 0);
  }

  100% {
    transform: translate(50px, 50px);
  }
}

.hero-title {
  color: white;
  font-size: 48px;
  font-weight: 700;
  line-height: 1.2;
  position: relative;
  z-index: 1;
}

.text-light-blue {
  color: var(--text-light);
}

.hero-section .btn-primary {
  background: #3B4DA8;
  border: none;
  padding: 12px 32px;
  border-radius: 6px;
  font-weight: 600;
}

.hero-section .btn-outline-light {
  border: 2px solid white;
  padding: 12px 32px;
  border-radius: 6px;
  font-weight: 600;
}

.hero-devices {
  position: relative;
  height: 400px;
  z-index: 1;
}

.hero-devices img {
  position: absolute;
  border-radius: 8px;
  width: 90%;

}

.device-main {
  width: 55%;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  z-index: 3;
}

.device-left {
  width: 35%;
  top: 20px;
  left: 0;
  z-index: 2;
}

.device-right {
  width: 100%;
  top: 60px;
  right: 0;
  z-index: 1;
  margin-top: -120px;
}

/* Features Section */
.features-section {
  background: #F8F9FA;
  padding: 80px 0;
}

.features-section h2 {
  color: #2C3E50;
  font-weight: 700;
  font-size: 36px;
}

.feature-card {
  background: white;
  border: 2px solid #E0E4F0;
  border-radius: 12px;
  padding: 30px;
  transition: all 0.3s;
}

.feature-card:hover {
  border-color: var(--primary-blue);
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(74, 95, 191, 0.15);
}

.feature-title {
  color: #2C3E50;
  font-weight: 600;
  margin-bottom: 12px;
  font-size: 18px;
}

.feature-description {
  color: #6C757D;
  font-size: 14px;
  line-height: 1.6;
  margin: 0;
}

/* How It Works Section */
.how-it-works-section {
  background: #E8F3FA;
  padding: 80px 0;
}

.how-it-works-section h2 {
  color: #2C3E50;
  font-weight: 700;
  font-size: 36px;
}

.work-card {
  padding: 20px;
}

.work-image {
  height: 250px;
  overflow: hidden;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.work-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.work-title {
  color: #2C3E50;
  font-weight: 600;
  font-size: 18px;
}

.work-description {
  color: #6C757D;
  font-size: 14px;
}

.how-it-works-section .btn-primary {
  background: var(--primary-blue);
  border: none;
  padding: 12px 48px;
  border-radius: 6px;
  font-weight: 600;
}

/* Responsive */
@media (max-width: 991px) {
  .hero-title {
    font-size: 36px;
  }

  .hero-devices {
    margin-top: 50px;
    height: 300px;
  }

  .navbar-nav {
    margin-top: 20px;
  }

  .btn-get-trial {
    margin-top: 15px;
  }
}

@media (max-width: 767px) {
  .hero-title {
    font-size: 28px;
  }

  .features-section h2,
  .how-it-works-section h2 {
    font-size: 28px;
  }
}

/* Pricing Section */
.pricing-section {
  background: white;
  padding: 80px 0;
}

.pricing-section h2 {
  color: #2C3E50;
  font-weight: 700;
  font-size: 36px;
}

.pricing-toggle {
  width: 50px;
  height: 26px;
  cursor: pointer;
}

.pricing-toggle:checked {
  background-color: var(--primary-blue);
  border-color: var(--primary-blue);
}

.benefit-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 10px;
}

.benefit-item i {
  font-size: 24px;
  color: var(--primary-blue);
}

.benefit-item small {
  font-size: 11px;
  color: #6C757D;
  line-height: 1.3;
}

.pricing-card {
  background: white;
  border: 2px solid #E0E4F0;
  border-radius: 16px;
  overflow: hidden;
  transition: all 0.3s;
  position: relative;
}

.pricing-card.popular {
  border-color: var(--primary-blue);
  transform: scale(1.05);
  box-shadow: 0 10px 40px rgba(74, 95, 191, 0.2);
}

.pricing-card .card-header {
  background: #F8F9FA;
  padding: 30px 20px;
  text-align: center;
}

.pricing-card.popular .card-header {
  background: var(--primary-blue);
  color: rgb(0, 0, 0);
}

.pricing-card h4 {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 15px;
}

.price-tag {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  gap: 4px;
}

.price-tag .currency {
  font-size: 20px;
  font-weight: 600;
  margin-top: 5px;
}

.price-tag .price {
  font-size: 48px;
  font-weight: 700;
  line-height: 1;
}

.price-tag .period {
  font-size: 14px;
  font-weight: 500;
  margin-top: 8px;
}

.pricing-card .card-body {
  padding: 30px 20px;
}

.feature-list {
  list-style: none;
  padding: 0;
  margin: 0 0 25px 0;
}

.feature-list li {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  color: #2C3E50;
  font-size: 14px;
}

.feature-list i {
  color: var(--primary-blue);
  font-size: 18px;
  flex-shrink: 0;
}

.pricing-card .btn-primary {
  background: var(--primary-blue);
  border: none;
  padding: 12px;
  border-radius: 8px;
  font-weight: 600;
}

.pricing-card.popular .btn-primary {
  background: white;
  color: var(--primary-blue);
}

/* Testimonials Section */
.testimonials-section {
  background: linear-gradient(135deg, #4A5FBF 0%, #5B6FD8 100%);
  position: relative;
  overflow: hidden;
  padding: 80px 0;
}

.testimonials-section::before {
  content: '';
  position: absolute;
  bottom: -50px;
  left: 0;
  right: 0;
  height: 150px;
  background: white;
  border-radius: 50% 50% 0 0 / 100% 100% 0 0;
}

.testimonials-section h2 {
  color: white;
  font-weight: 700;
  font-size: 36px;
}

.testimonial-card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  height: 100%;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.testimonial-avatar i {
  font-size: 48px;
  color: var(--primary-blue);
}

.testimonial-card h6 {
  font-weight: 600;
  color: #2C3E50;
}

.rating i {
  color: #FFC107;
  font-size: 14px;
}

.testimonial-text {
  color: #6C757D;
  font-size: 13px;
  line-height: 1.6;
  margin: 0;
}

/* FAQ Section */
.faq-section {
  background: #F8F9FA;
  padding: 80px 0;
}



.circle-box {
  width: 146px;
  height: 146px;
  border: 2px solid #4262B3;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  margin: auto;
  transition: all 0.3s ease;
}



.circle-number {
  background-color: #4262B3;
  color: white;
  font-weight: 600;
  width: 35px;
  height: 35px;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 14px;
  margin-bottom: 8px;
}

.circle-box {
  width: 140px;
  height: 140px;
  border: 2px solid #4262B3;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  margin: auto;
  text-align: center;
  padding: 10px;
  /* allows multi-line text inside */
  transition: all 0.3s ease;
}

.circle-text {
  font-size: 14px;
  color: #1b2850;
  font-weight: 500;
  text-align: center;
  line-height: 1.3em;
  white-space: normal;
  /* allows text to wrap */
  word-wrap: break-word;
  max-width: 90px;
  /* ensures wrapping happens neatly */
}

.faq-section h2 {
  color: #2C3E50;
  font-weight: 700;
  font-size: 36px;
}

.faq-item {
  background: white;
  border-radius: 8px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.3s;
  border: 1px solid #E0E4F0;
}

.faq-item:hover {
  border-color: var(--primary-blue);
  box-shadow: 0 4px 15px rgba(74, 95, 191, 0.1);
}

.faq-question {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  color: #2C3E50;
  font-size: 15px;
}

.faq-question i {
  font-size: 20px;
  color: var(--primary-blue);
  flex-shrink: 0;
}

.faq-answer {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #E0E4F0;
  color: #6C757D;
  font-size: 14px;
  line-height: 1.6;
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.faq-section .btn-primary {
  background: var(--primary-blue);
  border: none;
  padding: 12px 48px;
  border-radius: 6px;
  font-weight: 600;
}

#free-trial input.form-control {
  background-color: #111;
  border: 1px solid #444;
  color: #fff;
}

#free-trial input.form-control::placeholder {
  color: #ccc;
}

#free-trial .btn-primary {
  background-color: #2563eb;
  border: none;
}

#free-trial .bg-primary {
  background: rgba(37, 99, 235, 0.9) url('your-background-image.jpg') center/cover no-repeat;
}

.footer a:hover {
  color: #2563eb;
}


.bg-dark {
  background: #000 !important;
}

input {
  border: 1px solid #fff !important;
}
</style>