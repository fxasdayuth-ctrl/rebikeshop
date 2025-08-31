// ฟังก์ชันใช้งานทั่วไป
document.addEventListener('DOMContentLoaded', function() {
    // เปิดการยืนยันการลบข้อมูล
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('คุณแน่ใจว่าต้องการลบรายการนี้?')) {
                e.preventDefault();
            }
        });
    });
    
    // ฟังก์ชันสำหรับฟอร์มค้นหา
    const searchForms = document.querySelectorAll('.search-form');
    searchForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[type="search"]');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                alert('กรุณากรอกคำค้นหา');
                searchInput.focus();
            }
        });
    });
});

// ฟังก์ชันแปลงรูปแบบวันที่
function formatDate(dateString) {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('th-TH', options);
}

// ฟังก์ชันแปลงรูปแบบวันที่และเวลา
function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateTimeString).toLocaleDateString('th-TH', options);
}

// ฟังก์ชันแสดงการแจ้งเตือน
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.querySelector('.container').prepend(alertDiv);
    
    // ลบการแจ้งเตือนหลังจาก 5 วินาที
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}