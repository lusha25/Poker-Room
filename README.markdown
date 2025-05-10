# Poker Room

![image](https://github.com/user-attachments/assets/00411246-3460-4fd2-9f27-1e668d880455)

![image](https://github.com/user-attachments/assets/183eb6a9-5246-4d21-888e-30d9eadc1f6e)

This project is a web-based card game where players compete to achieve the highest total card value over multiple rounds.

## Features
- Configurable game with **1-3 players**, **1-5 cards per round**, and **1-5 rounds**
- Random card dealing from a **52-card deck**
- Card values: **2–10** (nominal), **A = 11**, **J/Q/K = 10**
- Interactive card revealing with point tracking
- Tiebreaker rounds for resolving draws
- Responsive design with a poker table theme
- Rules pop-up for gameplay instructions

## Technologies Used
- **PHP** (session management, game logic)
- **HTML** (page structure)
- **CSS** (external stylesheets: `style.css`, `form.css`)
- **JavaScript** (form validation, card revealing via fetch)
- **Bootstrap**-inspired styling (customized for poker theme)

## How to Play
- On the start page, set the number of **players** (1–3), **cards per round** (1–5), and **rounds** (1–5).
- Enter player names (defaults to "Igralec X" if blank) and click **"Začni igro"**.
- Click **"Razkrij karte"** to reveal each player's cards and view points.
- After all cards are revealed, see the total score for each player.
- Click **"Nove karte"** for the next round (if multiple rounds are set).
- In case of a tie, click **"Naslednja runda preboja"** for a tiebreaker.
- Click **"Nazaj"** to reset the game.
- View rules by clicking **"Prikaži pravila"** on the start page.

## Author
Developed by **Luka Dragan**

Enjoy playing Poker Room!
