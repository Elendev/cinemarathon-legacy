# Legacy cinemarathon.ch website
This repository contains the legacy code used to make the https://cinemarathon.ch ewbsite.

The new version is available in the new Cinemarathon repository : https://github.com/Elendev/cinemarathon

## Warning - DEPRECATED
This code is not used anymore and hasn't been updated for a very long time. It uses deprecated libraries.

Use it at your own risk.

# About this project
This repository contains code of the first version of the Cinemarathon.ch project. It uses Symfony 2.4.

The purpose of the Cinemarathon.ch is to provide a way to help organize to go to the Theater when we want 
to see multiple movies the same night.

Every morning at 4h, the website parse the https://pathe.ch website to scrap the list of available movies, 
save it into a cache and use it to calculate "marathon" of movies, based on user-defined criterion.
