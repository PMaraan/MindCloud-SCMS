const slidesWrapper = document.getElementById("slidesWrapper");
const slides = document.querySelectorAll(".slide");
const indicatorContainer = document.getElementById("indicatorContainer");

let index = 0;
let autoSlide;

// Create indicators
slides.forEach((_, i) => {
    const dot = document.createElement("div");
    dot.classList.add("slide-dot");
    dot.addEventListener("click", () => goToSlide(i));
    indicatorContainer.appendChild(dot);
});

// Update indicators
function updateIndicators() {
    document.querySelectorAll(".slide-dot").forEach((dot, i) => {
        dot.classList.toggle("active", i === index);
    });
}

// Go to a specific slide with blur
function goToSlide(i) {
    slides.forEach(slide => slide.classList.add("sliding")); // blur

    setTimeout(() => {
        index = i;
        slidesWrapper.style.transform = `translateX(-${index * 100}%)`;
        updateIndicators();
        slides.forEach(slide => slide.classList.remove("sliding")); // remove blur
    }, 50);
}

// Navigation
function nextSlide() {
    index = (index + 1) % slides.length;
    goToSlide(index);
}

function prevSlide() {
    index = (index - 1 + slides.length) % slides.length;
    goToSlide(index);
}

// Autoplay
function startAutoSlide() {
    autoSlide = setInterval(nextSlide, 5000);
}

function stopAutoSlide() {
    clearInterval(autoSlide);
}

// Arrow events
document.getElementById("nextSlide").addEventListener("click", () => { stopAutoSlide(); nextSlide(); startAutoSlide(); });
document.getElementById("prevSlide").addEventListener("click", () => { stopAutoSlide(); prevSlide(); startAutoSlide(); });

// Initialize
goToSlide(0);
startAutoSlide();
