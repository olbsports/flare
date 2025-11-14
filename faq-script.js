// FAQ SCRIPT

document.addEventListener('DOMContentLoaded', function() {
    const accordion = document.getElementById('faqAccordion');
    const searchInput = document.getElementById('faqSearch');
    const tabs = document.querySelectorAll('.faq-tab');
    
    // Render FAQ items
    function renderFAQ(items) {
        accordion.innerHTML = '';
        items.forEach((item, index) => {
            const faqItem = document.createElement('div');
            faqItem.className = 'faq-item';
            faqItem.dataset.category = item.category;
            
            faqItem.innerHTML = `
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span class="faq-question-text">${item.question}</span>
                    <div class="faq-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9L12 16L5 9"/>
                        </svg>
                    </div>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer-content">${item.answer}</div>
                </div>
            `;
            
            accordion.appendChild(faqItem);
        });
    }
    
    // Initial render
    renderFAQ(faqData);
    
    // Toggle FAQ
    window.toggleFaq = function(button) {
        const item = button.parentElement;
        const wasActive = item.classList.contains('active');
        
        document.querySelectorAll('.faq-item').forEach(i => {
            i.classList.remove('active');
        });
        
        if (!wasActive) {
            item.classList.add('active');
        }
    };
    
    // Filter by category
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const category = this.dataset.category;
            
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const filteredData = category === 'all' 
                ? faqData 
                : faqData.filter(item => item.category === category);
            
            renderFAQ(filteredData);
            searchInput.value = '';
        });
    });
    
    // Search
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        if (searchTerm.length === 0) {
            const activeTab = document.querySelector('.faq-tab.active');
            const category = activeTab.dataset.category;
            const filteredData = category === 'all' 
                ? faqData 
                : faqData.filter(item => item.category === category);
            renderFAQ(filteredData);
            return;
        }
        
        const searchResults = faqData.filter(item => {
            return item.question.toLowerCase().includes(searchTerm) || 
                   item.answer.toLowerCase().includes(searchTerm);
        });
        
        renderFAQ(searchResults);
        
        // Auto-open search results
        document.querySelectorAll('.faq-item').forEach(item => {
            item.classList.add('active');
        });
        
        // Reset category filter
        tabs.forEach(t => t.classList.remove('active'));
    });
});
