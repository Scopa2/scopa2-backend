1. AuthController::login() L32 — validates 'username' => 'required|string|email' — email format check on a
   username field, always fails
2. GameController::create() L34 — sets status to PLAYING even with no player_2 (should be WAITING_FOR_PLAYERS)
3. GameController::handleAction() L85-100 — validation and try-catch commented out (TODO)
4. GameController._handleAction() L119 — sleep(5) blocking call inside round-end callback — kills production
   throughput
5. ScopaEngine::advanceRound() L294 — first player alternation never implemented (commented out)
6. MoveValidator::validateBuyAction/validateUseAction() — always return true, unimplemented
7. GameState::toPublicView() L75 — exports full mutations map, can leak opponent card identities via San Biagio
8. routes/ai.php — imports non-existent ScopaGodotServer class (fatal if route hit)
