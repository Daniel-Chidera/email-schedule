/**
 * EmailScheduler - Main JavaScript File
 * Handles mobile menu, smooth scrolling, and form interactions
 */

// Get mobile menu elements
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

/**
 * Toggle mobile menu when hamburger is clicked
 */
hamburger.addEventListener('click', () => {
    navMenu.classList.toggle('active');
    hamburger.classList.toggle('active');
});

/**
 * Close mobile menu when any navigation link is clicked
 * This improves user experience on mobile devices
 */
document.querySelectorAll('.nav-menu a').forEach(link => {
    link.addEventListener('click', () => {
        navMenu.classList.remove('active');
        hamburger.classList.remove('active');
    });
});

/**
 * Smooth scrolling for all anchor links
 * Applies to links that start with # (internal page sections)
 */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Get the target section
        const target = document.querySelector(this.getAttribute('href'));
        
        if (target) {
            // Smoothly scroll to the target section
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

/**
 * Add shadow to header on scroll
 * Creates a subtle shadow effect when user scrolls down
 */
const header = document.querySelector('.header');
window.addEventListener('scroll', () => {
    if (window.scrollY > 0) {
        header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.08)';
    } else {
        header.style.boxShadow = 'none';
    }
});

/**
 * Handle contact form submission
 * Prevents default form submission and shows success message
 */
const contactForm = document.querySelector('.contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Show success message
        alert('Thank you for your message! We will get back to you soon.');
        
        // Reset form fields
        contactForm.reset();
    });
}