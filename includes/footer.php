    </div> <!-- Cierre de main-content -->

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <h3 style="color: white; margin-bottom: 1rem;">
                        <i class="fas fa-church"></i> Asociación de Iglesias
                    </h3>
                    <p style="color: rgba(255,255,255,0.8);">Unidos en fe, esperanza y amor para servir a nuestra comunidad en el estado de Guerrero.</p>
                </div>
                
                <div>
                    <h4 style="color: white; margin-bottom: 1rem;">Enlaces Rápidos</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li><a href="<?php echo $base_url; ?>/index.php" style="color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 0.25rem 0;">Inicio</a></li>
                        <li><a href="<?php echo $base_url; ?>/blog/index.php" style="color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 0.25rem 0;">Blog</a></li>
                        <li><a href="#about" style="color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 0.25rem 0;">Nosotros</a></li>
                        <li><a href="#contact" style="color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 0.25rem 0;">Contacto</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: white; margin-bottom: 1rem;">Contacto</h4>
                    <div style="color: rgba(255,255,255,0.8);">
                        <p><i class="fas fa-map-marker-alt"></i> Guerrero, México</p>
                        <p><i class="fas fa-phone"></i> +52 744 123 4567</p>
                        <p><i class="fas fa-envelope"></i> info@asociacioniglesias.org</p>
                    </div>
                </div>
            </div>
            
            <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem; text-align: center;">
                <p style="color: rgba(255,255,255,0.7);">
                    &copy; <?php echo date('Y'); ?> Asociación de Iglesias del Estado de Guerrero. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="<?php echo $base_url; ?>/assets/js/main.js"></script>
</body>
</html>