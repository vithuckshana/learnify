<?php require_once __DIR__ . '/../config/app.php'; ?>

<?php if (empty($is_hero) && empty($is_auth)): ?>
</div><!-- .container -->
</main>
<?php endif; ?>

<?php if (empty($is_auth)): ?>
<footer class="site-footer">
    <p>© <?php echo date('Y'); ?> Learnify · Tutor Booking Platform</p>
    <p class="version">v1.0 · cinematic.dark</p>
</footer>
<?php endif; ?>

</body>
</html>
