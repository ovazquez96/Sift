# Sift

Este modulo es una integración entre una API anti fraudes, la cual contiene distintos eventos para analizar y dar puntuaciones.

Por ejemplo, un evento que se usa al momento de crear una cuenta, o un evento al momento de crear una orden, Sift dará una calificación del 0 al 100 donde un puntaje de 100 es áltamente riesgoso.

También generé una parte administrable en el backend de Magento, donde se puede introducir la api key de Sift.
Utilicé principalmente Observers, para poder poner los eventos de sift en los eventos de Magento.

Los Observers se encuentran en /Transom/SiftModule/Observer/Events

En Transom/SiftModule/view/adminhtml se encuentra la vista del admin panel para configurar el módulo.

En Transom/SiftModule/etc/adminhtml se encuentra la configuración del menú, los eventos, las rutas del backend, etc.

En Transom/SiftModule/etc/frontend se encuentra la configuración de rutas y eventos del frontend.
