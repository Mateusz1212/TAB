    <div style="margin: 50px auto; width: 300px; border: 1px solid black; padding: 20px;">
        <h3>Logowanie (Widok Kompaktowy)</h3>
        <form action="panel.php" method="POST">
            <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
                <tr><td align="right">Inicjały:</td><td><input type="text" name="imie" size="16" maxlength="16"></td></tr>
                <tr><td align="right">Hasło:</td><td><input type="password" name="password" size="8" maxlength="8"></td></tr>
            </table>
            <div style="text-align:center; margin-top:10px;">
                <input type="submit" value="Zaloguj się">
            </div>
        </form>
        <?php if (!empty($login_err)) echo "<div class='error-msg'>$login_err</div>"; ?>
    </div>
