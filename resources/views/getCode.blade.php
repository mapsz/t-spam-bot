<form action="/send/code">
  <label for="code">Код подтверждения:</label>
  <input id="code" type="text" name="code" required>
  <input id="code" type="hidden" name="login" value="{{ $login }}" required>
  <button type="submit">ok</button>
</form>