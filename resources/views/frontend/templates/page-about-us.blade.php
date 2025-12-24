@extends('frontend.layout')

@section('title', $node->title ?? $title ?? 'Page')

@push('styles')
<style>
* { box-sizing: border-box; } body {margin: 0;}*{box-sizing:border-box;}body{margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;}.container{max-width:1200px;margin-top:0px;margin-right:auto;margin-bottom:0px;margin-left:auto;padding-top:40px;padding-right:20px;padding-bottom:40px;padding-left:20px;font-family:Arial, sans-serif;line-height:1.6;}h1{color:rgb(255, 102, 0);font-size:2.5em;margin-bottom:20px;}h2{color:rgb(51, 51, 51);font-size:1.8em;margin-top:30px;margin-bottom:15px;}p{color:rgb(85, 85, 85);font-size:1.1em;margin-bottom:15px;}ul{margin-top:20px;margin-right:0px;margin-bottom:20px;margin-left:0px;padding-left:20px;}li{margin-bottom:15px;color:rgb(85, 85, 85);}strong{color:rgb(255, 102, 0);}
</style>
@endpush

@section('content')
<div class="container"><h1>Η WebOrange</h1><p>Η WebOrange είναι μια καινοτόμος εταιρεία πληροφορικής που εξειδικεύεται στην ανάπτυξη σύγχρονων ψηφιακών λύσεων. Με πάθος για την τεχνολογία και προσήλωση στην ποιότητα, δημιουργούμε εφαρμογές και ιστοσελίδες που ξεχωρίζουν.</p><h2>Οι Υπηρεσίες μας</h2><ul><li><strong>Κατασκευή Ιστοσελίδων:</strong> Σχεδιάζουμε και αναπτύσσουμε responsive ιστοσελίδες που συνδυάζουν εξαιρετική αισθητική με άριστη λειτουργικότητα.</li><li><strong>Ανάπτυξη Εφαρμογών:</strong> Δημιουργούμε custom εφαρμογές web και mobile που καλύπτουν τις ειδικές ανάγκες της επιχείρησής σας.</li><li><strong>Ψηφιακές Λύσεις:</strong> Προσφέρουμε ολοκληρωμένες λύσεις που βοηθούν την επιχείρησή σας να αναπτυχθεί στον ψηφιακό κόσμο.</li></ul><h2>Γιατί WebOrange;</h2><p>Συνδυάζουμε την τεχνική εμπειρία με τη δημιουργική σκέψη για να παραδώσουμε projects που ξεπερνούν τις προσδοκίες. Η ομάδα μας αποτελείται από έμπειρους developers και designers που αγαπούν αυτό που κάνουν.</p><p>Επικοινωνήστε μαζί μας σήμερα και ας δημιουργήσουμε μαζί το επόμενο επιτυχημένο σας project!</p></div>
@endsection
