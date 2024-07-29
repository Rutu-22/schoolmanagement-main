import { Component, OnInit } from '@angular/core';
import { SchoolsService } from './schools.service';

@Component({
  templateUrl: './schools.component.html',
  styleUrls: ['./schools.component.scss']
})
export class SchoolsComponent implements OnInit {
  schools:any;
  constructor(private SchoolsService:SchoolsService){

  }
 ngOnInit(): void {
   this.SchoolsService.gets().subscribe((results:any)=>{
    this.schools=results.data.schools;
   })
 }
}
