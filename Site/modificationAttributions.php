<?php

echo "<title>Accueil > Attribution chambres > Modification Attributions</title>";
include("_debut.inc.php");
include("_gestionBase.inc.php"); 
include("_controlesEtGestionErreurs.inc.php");

// CONNEXION AU SERVEUR MYSQL PUIS SÉLECTION DE LA BASE DE DONNÉES festival

$connexion=connect();
if (!$connexion)
{
   ajouterErreur("Echec de la connexion au serveur MySql");
   afficherErreurs();
   exit();
}
//if (!selectBase($connexion))
//{
//   ajouterErreur("La base de données festival est inexistante ou non accessible");
//   afficherErreurs();
//   exit();
//}

// EFFECTUER OU MODIFIER LES ATTRIBUTIONS POUR L'ENSEMBLE DES ÉTABLISSEMENTS

// CETTE PAGE CONTIENT UN TABLEAU CONSTITUÉ DE 2 LIGNES D'EN-TÊTE (LIGNE TITRE ET 
// LIGNE ÉTABLISSEMENTS) ET DU DÉTAIL DES ATTRIBUTIONS 
// UNE LÉGENDE FIGURE SOUS LE TABLEAU

// Recherche du nombre d'établissements offrant des chambres pour le 
// dimensionnement des colonnes
$nbEtabOffrantChambres=obtenirNbEtabOffrantChambres($connexion);
$nb=$nbEtabOffrantChambres+3;
// Détermination du pourcentage de largeur des colonnes "établissements"
$pourcCol=50/$nbEtabOffrantChambres;

$action=$_REQUEST['action'];

// Si l'action est validerModifAttrib (cas où l'on vient de la page 
// donnerNbChambres.php) alors on effectue la mise à jour des attributions dans 
// la base 
if ($action=='validerModifAttrib')
{
   $idEtab=$_REQUEST['idEtab'];
   $idGroupe=$_REQUEST['idGroupe'];
   $nbChambres=$_REQUEST['nbChambres'];
   modifierAttribChamb($connexion, $idEtab, $idGroupe, $nbChambres);
}

echo "
<table width='80%' cellspacing='0' cellpadding='0' align='center' 
class='tabQuadrille'>";

   // AFFICHAGE DE LA 1ÈRE LIGNE D'EN-TÊTE
   echo "
   <tr class='enTeteTabQuad'>
      <td colspan=$nb><strong>Attributions</strong></td>
   </tr>";
      
   // AFFICHAGE DE LA 2ÈME LIGNE D'EN-TÊTE (ÉTABLISSEMENTS)
   echo "
   <tr class='ligneTabQuad'>
      <td>Nom Groupe</td>
      <td>Nombre de chambres demandées";
      
   $req=obtenirReqEtablissementsOffrantChambres();
   $rsEtab=$connexion->query($req); // modification de la ligne de code en pdo
   $lgEtab=$rsEtab->fetchAll(); // modification de la ligne de code en pdo

   // Boucle sur les établissements (pour afficher le nom de l'établissement et 
   // le nombre de chambres encore disponibles)
   
   foreach ($lgEtab as $row)
   {
      $idEtab=$row["id"];
      $nom=$row["nom"];
      $nbOffre=$row["nombreChambresOffertes"];
      $nbOccup=obtenirNbOccup($connexion, $idEtab);
                    
      // Calcul du nombre de chambres libres
      $nbChLib = $nbOffre - $nbOccup;
      echo "
      <td valign='top' width='$pourcCol%'><i>Disponibilités : $nbChLib </i> <br>
      $nom </td>";
      $lgEtab=$rsEtab->fetchAll(); // modification de la ligne de code en pdo
   } 
   echo "
      <td>Nombre de chambres réservées</td>";

   // CORPS DU TABLEAU : CONSTITUTION D'UNE LIGNE PAR GROUPE À HÉBERGER AVEC LES 
   // CHAMBRES ATTRIBUÉES ET LES LIENS POUR EFFECTUER OU MODIFIER LES ATTRIBUTIONS
         
   $req=obtenirReqIdNomGroupesAHeberger();
   $rsGroupe=$connexion->query($req); // modification de la ligne de code en pdo
   $lgGroupe=$rsGroupe->fetchAll(); // modification de la ligne de code en pdo
         
   // BOUCLE SUR LES GROUPES À HÉBERGER 
   foreach ($lgGroupe as $row)
   {
      $idGroupe=$row['id'];
      $nom=$row['nom'];
      $nompays=$row['nompays'];
      $nbChambres =  intdiv($row['nombrePersonnes'],3);
      if($row['nombrePersonnes']%3>0)
      {
         $nbChambres = $nbChambres + 1;
      }
      $nbChambrestotal = 0;
      echo "
      <tr class='ligneTabQuad'>
         <td width='25%'>$nom ($nompays)</td>
         <td> $nbChambres </td>"; // affiche les nombre de chambres demandées
      $req=obtenirReqEtablissementsOffrantChambres();
      $rsEtab=$connexion->query($req); // modification de la ligne de code en pdo
      $lgEtab=$rsEtab->fetchAll(); // modification de la ligne de code en pdo
           
      // BOUCLE SUR LES ÉTABLISSEMENTS
      foreach ($lgEtab as $row)
      {
         $idEtab=$row["id"];
         $nbOffre=$row["nombreChambresOffertes"];
         $nbOccup=obtenirNbOccup($connexion, $idEtab);
         echo"<form method='POST' action='modificationAttributions.php'>
         <input type='hidden' value='validerModifAttrib' name='action'>
         <input type='hidden' value='$idEtab' name='idEtab'>
         <input type='hidden' value='$idGroupe' name='idGroupe'>";          
         // Calcul du nombre de chambres libres
         $nbChLib = $nbOffre - $nbOccup;
                  
         // On recherche si des chambres ont déjà été attribuées à ce groupe
         // dans cet établissement
         $nbOccupGroupe=obtenirNbOccupGroupe($connexion, $idEtab, $idGroupe);
         $nbChambrestotal += $nbOccupGroupe;
         // Cas où des chambres ont déjà été attribuées à ce groupe dans cet
         // établissement
         if ($nbOccupGroupe!=0)
         {
            // Le nombre de chambres maximum pouvant être demandées est la somme 
            // du nombre de chambres libres et du nombre de chambres actuellement 
            // attribuées au groupe (ce nombre $nbmax sera transmis si on 
            // choisit de modifier le nombre de chambres)
            $nbMax = $nbChLib + $nbOccupGroupe;
            echo "
            <td class='reserve'>
             <select name ='nbChambres'>"; // menu déroulant 
             for ($i=0;$i<=$nbMax; $i++)
             {
                
                if ($nbOccupGroupe == $i)
                echo "<option selected> $i </option>";
                else
                echo "<option>$i</option>";
             } 
             echo "</select>&nbsp<input type='submit' value='valider'></form></td>";

         }
         else
         {
            // Cas où il n'y a pas de chambres attribuées à ce groupe dans cet 
            // établissement : on affiche un lien vers donnerNbChambres s'il y a 
            // des chambres libres sinon rien n'est affiché     
            if ($nbChLib != 0)
            {
               if ($nbChLib<$nbChambres-$nbChambrestotal)
               $nbMax=$nbChLib;
               else $nbMax=$nbChambres-$nbChambrestotal;
               echo "
               <td class='reserveSiLien'>
               <select name ='nbChambres'><option selected>0</option>"; // menu déroulant 
             for ($i=1;$i<=$nbMax; $i++)
             {
                echo "<option>$i</option>";
             } 
             echo "</select>&nbsp<input type='submit' value='valider'></form></td>";
            }
            else
            {
               echo "<td class='reserveSiLien'>&nbsp;</td>";
            }
         }    
         $lgEtab=$rsEtab->fetchAll(); // modification de la ligne de code en pdo
      } // Fin de la boucle sur les établissements    
      $lgGroupe=$rsGroupe->fetchAll(); // modification de la ligne de code en pdo
      echo "
   <td> $nbChambrestotal </td>"; // affichage  du nombre de chambres total dans la colone des chambres réservées
   } // Fin de la boucle sur les groupes à héberger
echo "
</table>"; // Fin du tableau principal

// AFFICHAGE DE LA LÉGENDE
echo "
<table align='center' width='80%'>
   <tr>
      <td width='34%' align='left'><a href='consultationAttributions.php'>Retour</a>
      </td>
      <td class='reserveSiLien'>&nbsp;</td>
      <td width='30%' align='left'>Réservation possible si lien</td>
      <td class='reserve'>&nbsp;</td>
      <td width='30%' align='left'>Chambres réservées</td>
   </tr>
</table>";

?>
