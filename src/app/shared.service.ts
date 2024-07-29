
import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class SharedService {
  private selectedAcademicYear: string;

  setSelectedAcademicYear(year: string) {
    this.selectedAcademicYear = year;
  }

  getSelectedAcademicYear(): string {
    return this.selectedAcademicYear;
  }
}
