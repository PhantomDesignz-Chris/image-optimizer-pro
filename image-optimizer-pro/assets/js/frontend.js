jQuery(document).ready(function($) {
    // Image comparison slider functionality
    if ($('.iop-comparison-container').length) {
        $('.iop-comparison-container').each(function() {
            const container = $(this);
            const slider = container.find('.iop-image-slider');
            const handle = container.find('.iop-slider-handle');
            const imgWidth = container.width();
            
            // Set initial position
            slider.css('width', '50%');
            
            // Make slider draggable
            handle.on('mousedown touchstart', function(e) {
                e.preventDefault();
                $(document).on('mousemove touchmove', moveHandler);
                $(document).on('mouseup touchend', stopHandler);
            });
            
            function moveHandler(e) {
                let posX = e.pageX || e.originalEvent.touches[0].pageX;
                let containerOffset = container.offset().left;
                let containerWidth = container.width();
                let relativeX = posX - containerOffset;
                
                // Constrain within container
                relativeX = Math.max(0, Math.min(relativeX, containerWidth));
                
                // Update slider width
                let percentage = (relativeX / containerWidth) * 100;
                slider.css('width', percentage + '%');
            }
            
            function stopHandler() {
                $(document).off('mousemove touchmove', moveHandler);
                $(document).off('mouseup touchend', stopHandler);
            }
        });
    }
    
    // Other frontend interactions can be added here
});